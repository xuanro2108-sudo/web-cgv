import json
import os
import re
import time
import urllib.error
import urllib.request

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select, WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


BASE_URL = "http://127.0.0.1:5173"
API_URL = "http://127.0.0.1:8000/api"

ADMIN_EMAIL = "admin@gmail.com"
ADMIN_PASSWORD = "123456"

ACTION_DELAY = 2.0
EVIDENCE_DIR = "evidence"
os.makedirs(EVIDENCE_DIR, exist_ok=True)


def cho(seconds=ACTION_DELAY):
    time.sleep(seconds)


def chup_anh(driver, name):
    driver.save_screenshot(
        os.path.join(EVIDENCE_DIR, name)
    )


def api_request(method, path, data=None, token=None):
    body = None
    headers = {
        "Accept": "application/json",
    }

    if data is not None:
        body = json.dumps(data).encode("utf-8")
        headers["Content-Type"] = "application/json"

    if token:
        headers["Authorization"] = f"Bearer {token}"

    request = urllib.request.Request(
        f"{API_URL}{path}",
        data=body,
        headers=headers,
        method=method,
    )

    try:
        with urllib.request.urlopen(request, timeout=20) as response:
            raw = response.read().decode("utf-8")
            return (
                response.status,
                json.loads(raw) if raw else {},
            )
    except urllib.error.HTTPError as error:
        raw = error.read().decode("utf-8")
        try:
            payload = json.loads(raw)
        except Exception:
            payload = {"message": raw}

        return error.code, payload


def dang_nhap_admin():
    status, data = api_request(
        "POST",
        "/auth/internal/login",
        {
            "email": ADMIN_EMAIL,
            "matKhau": ADMIN_PASSWORD,
        },
    )

    assert status == 200, (
        f"Không đăng nhập được QUAN_LY. "
        f"HTTP {status}: {data.get('message', data)}"
    )

    return data


def dat_phien_admin(driver, login_data):
    driver.get(f"{BASE_URL}/home")
    cho(1)

    account = login_data.get("taiKhoan", {})

    driver.execute_script(
        """
        localStorage.clear();
        localStorage.setItem('token', arguments[0]);
        localStorage.setItem('vaiTro', arguments[1]);
        localStorage.setItem('taiKhoan', arguments[2]);
        """,
        login_data["token"],
        account.get("vaiTro", "QUAN_LY"),
        json.dumps(account, ensure_ascii=False),
    )


@pytest.fixture(scope="module")
def admin():
    return dang_nhap_admin()


@pytest.fixture
def driver():
    browser = webdriver.Chrome()
    browser.maximize_window()
    yield browser
    browser.quit()


def mo_trang_phong(driver, admin):
    dat_phien_admin(driver, admin)

    driver.get(
        f"{BASE_URL}/dashboard/phong-chieu"
    )
    cho()

    WebDriverWait(driver, 15).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Quản lý phòng chiếu']",
            )
        )
    )

    WebDriverWait(driver, 15).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".pcm-room-card",
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".pcm-empty",
                )
            ) > 0
    )


def lay_phong_dau_tien():
    status, data = api_request(
        "GET",
        "/phong-chieus",
    )

    assert status == 200, (
        f"Không tải được danh sách phòng. HTTP {status}"
    )

    rooms = data.get("data", [])

    if not rooms:
        pytest.skip(
            "Hệ thống chưa có phòng chiếu."
        )

    # Ưu tiên P001 nếu có để dễ quan sát.
    room = next(
        (
            item
            for item in rooms
            if item.get("maPhong") == "P001"
        ),
        rooms[0],
    )

    return room


def chon_phong(driver, ma_phong):
    cards = driver.find_elements(
        By.CSS_SELECTOR,
        ".pcm-room-card",
    )

    card = next(
        (
            item
            for item in cards
            if ma_phong in item.text
        ),
        None,
    )

    assert card is not None, (
        f"Không tìm thấy phòng {ma_phong} trên giao diện."
    )

    cho()
    driver.execute_script(
        "arguments[0].click();",
        card,
    )
    cho()

    WebDriverWait(driver, 15).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".pcm-detail-card",
            )
        )
    )

    WebDriverWait(driver, 15).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".pcm-seat-map .pcm-seat",
                )
            ) > 0
            or "Phòng chưa có sơ đồ ghế."
            in d.find_element(
                By.CSS_SELECTOR,
                ".pcm-detail-card",
            ).text
    )


def lay_so_do_cua_phong(ma_phong):
    status, room_response = api_request(
        "GET",
        f"/phong-chieus/{ma_phong}",
    )

    assert status == 200

    room = room_response.get("data", {})
    seat_map_ref = (
        room.get("so_do_ghe")
        or room.get("soDoGhe")
        or {}
    )

    ma_so_do = seat_map_ref.get("maSoDo")

    assert ma_so_do, (
        f"Phòng {ma_phong} chưa có sơ đồ ghế."
    )

    status, seat_response = api_request(
        "GET",
        f"/so-do-ghes/{ma_so_do}",
    )

    assert status == 200

    return seat_response.get("data", {})


def ma_ghe_theo_nhan(ma_phong, label):
    seat_map = lay_so_do_cua_phong(
        ma_phong
    )

    match = re.fullmatch(
        r"([A-Z]+)(\d+)",
        label.strip(),
    )

    assert match, (
        f"Không đọc được nhãn ghế: {label}"
    )

    hang = match.group(1)
    cot = int(match.group(2))

    seat = next(
        (
            item
            for item in seat_map.get("ghes", [])
            if str(item.get("hang")) == hang
            and int(item.get("cot", 0)) == cot
        ),
        None,
    )

    assert seat is not None, (
        f"Không tìm thấy ghế {label} trong API."
    )

    return seat["maGhe"]


def mo_khoa_ghe(driver, button):
    label = button.text.strip()

    driver.execute_script(
        "arguments[0].click();",
        button,
    )

    WebDriverWait(driver, 5).until(
        EC.alert_is_present()
    )

    alert = driver.switch_to.alert

    assert (
        "Khóa ghế"
        in alert.text
    )

    cho(1)
    alert.accept()
    cho()

    WebDriverWait(driver, 10).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".pcm-alert-success",
            )
        )
    )

    return label


# ==========================================================
# TC23 - XEM DANH SÁCH VÀ SƠ ĐỒ PHÒNG CHIẾU
# FR-40, FR-42
# ==========================================================
def test_TC23_xem_danh_sach_va_so_do_phong(driver, admin):
    print("\n========================================")
    print("TC23 - XEM DANH SÁCH VÀ SƠ ĐỒ PHÒNG CHIẾU")
    print("========================================")

    room = lay_phong_dau_tien()
    ma_phong = room["maPhong"]

    # BƯỚC 1
    print("Bước 1: Mở Quản lý phòng chiếu")

    mo_trang_phong(
        driver,
        admin,
    )

    cards = driver.find_elements(
        By.CSS_SELECTOR,
        ".pcm-room-card",
    )

    assert cards

    print(
        f"PASS Bước 1: Hiển thị {len(cards)} phòng"
    )

    # BƯỚC 2
    print(
        f"Bước 2: Chọn phòng {ma_phong}"
    )

    chon_phong(
        driver,
        ma_phong,
    )

    detail = driver.find_element(
        By.CSS_SELECTOR,
        ".pcm-detail-card",
    )

    assert ma_phong in detail.text
    assert "Sức chứa" in detail.text

    print(
        "PASS Bước 2: Hiển thị thông tin tên phòng, sức chứa và trạng thái"
    )

    # BƯỚC 3
    print("Bước 3: Xem sơ đồ ghế")

    seats = driver.find_elements(
        By.CSS_SELECTOR,
        ".pcm-seat-map .pcm-seat",
    )

    assert seats, (
        "Sơ đồ phòng không có ghế."
    )

    assert driver.find_elements(
        By.CSS_SELECTOR,
        ".pcm-seat-normal",
    ), "Không tìm thấy ghế thường."

    assert driver.find_elements(
        By.CSS_SELECTOR,
        ".pcm-seat-vip",
    ), "Không tìm thấy ghế VIP."

    assert driver.find_elements(
        By.CSS_SELECTOR,
        ".pcm-seat-couple",
    ), "Không tìm thấy ghế đôi."

    chup_anh(
        driver,
        "TC23_so_do_phong_chieu.png",
    )

    print(
        "PASS Bước 3: Sơ đồ hiển thị đúng các loại ghế"
    )

    cho(3)


# ==========================================================
# TC24 - KHÓA GHẾ THƯỜNG BỊ HỎNG
# FR-43
# ==========================================================
def test_TC24_khoa_ghe_thuong(driver, admin):
    print("\n========================================")
    print("TC24 - KHÓA GHẾ THƯỜNG BỊ HỎNG")
    print("========================================")

    room = lay_phong_dau_tien()
    ma_phong = room["maPhong"]

    ma_ghe = None

    try:
        # BƯỚC 1
        print("Bước 1: Mở sơ đồ phòng")

        mo_trang_phong(
            driver,
            admin,
        )

        chon_phong(
            driver,
            ma_phong,
        )

        print(
            "PASS Bước 1: Sơ đồ ghế hiển thị"
        )

        # BƯỚC 2
        print("Bước 2: Chọn một ghế thường đang hoạt động")

        normal_seats = driver.find_elements(
            By.CSS_SELECTOR,
            "button.pcm-seat-normal:not(.pcm-seat-locked)",
        )

        if not normal_seats:
            pytest.skip(
                "Không có ghế thường HOAT_DONG để kiểm thử."
            )

        button = normal_seats[0]
        label = button.text.strip()

        ma_ghe = ma_ghe_theo_nhan(
            ma_phong,
            label,
        )

        driver.execute_script(
            "arguments[0].click();",
            button,
        )

        WebDriverWait(driver, 5).until(
            EC.alert_is_present()
        )

        alert = driver.switch_to.alert

        assert (
            f"Khóa ghế {label}"
            in alert.text
        )

        print(
            "PASS Bước 2: Hệ thống yêu cầu xác nhận khóa ghế"
        )

        # BƯỚC 3
        print("Bước 3: Xác nhận khóa")

        cho(1)
        alert.accept()
        cho()

        success = WebDriverWait(
            driver,
            10,
        ).until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".pcm-alert-success",
                )
            )
        )

        assert (
            "Đã khóa ghế"
            in success.text
        )

        locked = driver.find_element(
            By.XPATH,
            f"//button[contains(@class,'pcm-seat') "
            f"and normalize-space(.)='{label}']",
        )

        assert (
            "pcm-seat-locked"
            in locked.get_attribute("class")
        )

        chup_anh(
            driver,
            "TC24_khoa_ghe_thuong.png",
        )

        print(
            f"PASS Bước 3: Ghế {label} chuyển sang KHOA"
        )

        cho(3)

    finally:
        if ma_ghe:
            api_request(
                "PUT",
                f"/ghes/{ma_ghe}",
                {
                    "trangThai": "HOAT_DONG",
                },
                token=admin["token"],
            )


# ==========================================================
# TC25 - KHÓA CẢ CẶP GHẾ ĐÔI
# FR-43
# ==========================================================
def test_TC25_khoa_ca_cap_ghe_doi(driver, admin):
    print("\n========================================")
    print("TC25 - KHÓA CẢ CẶP GHẾ ĐÔI")
    print("========================================")

    room = lay_phong_dau_tien()
    ma_phong = room["maPhong"]

    ma_ghe_1 = None
    ma_ghe_2 = None

    try:
        # BƯỚC 1
        print("Bước 1: Mở sơ đồ phòng có ghế đôi")

        mo_trang_phong(
            driver,
            admin,
        )

        chon_phong(
            driver,
            ma_phong,
        )

        couple_buttons = driver.find_elements(
            By.CSS_SELECTOR,
            ".pcm-seat-couple.pcm-seat-double:not(.pcm-seat-locked)",
        )

        if not couple_buttons:
            pytest.skip(
                "Không có cặp ghế đôi HOAT_DONG để kiểm thử."
            )

        print(
            "PASS Bước 1: Ghế đôi được gom thành từng cặp"
        )

        # BƯỚC 2
        print("Bước 2: Chọn một cặp ghế đôi")

        button = couple_buttons[0]
        pair_label = button.text.strip()

        labels = [
            item.strip()
            for item in pair_label.split("-")
            if item.strip()
        ]

        assert len(labels) == 2, (
            f"Nhãn ghế đôi không đúng dạng cặp: {pair_label}"
        )

        ma_ghe_1 = ma_ghe_theo_nhan(
            ma_phong,
            labels[0],
        )

        ma_ghe_2 = ma_ghe_theo_nhan(
            ma_phong,
            labels[1],
        )

        driver.execute_script(
            "arguments[0].click();",
            button,
        )

        WebDriverWait(driver, 5).until(
            EC.alert_is_present()
        )

        alert = driver.switch_to.alert

        assert (
            "Khóa ghế"
            in alert.text
        )

        print(
            "PASS Bước 2: Hệ thống yêu cầu xác nhận khóa cả cặp"
        )

        # BƯỚC 3
        print("Bước 3: Xác nhận khóa")

        cho(1)
        alert.accept()
        cho()

        WebDriverWait(driver, 10).until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".pcm-alert-success",
                )
            )
        )

        pair_after = driver.find_element(
            By.XPATH,
            f"//button[contains(@class,'pcm-seat-couple') "
            f"and normalize-space(.)='{pair_label}']",
        )

        assert (
            "pcm-seat-locked"
            in pair_after.get_attribute("class")
        )

        # Kiểm tra cả 2 ghế vật lý qua API.
        seat_map = lay_so_do_cua_phong(
            ma_phong
        )

        states = {
            seat["maGhe"]: seat["trangThai"]
            for seat in seat_map.get("ghes", [])
            if seat["maGhe"] in {
                ma_ghe_1,
                ma_ghe_2,
            }
        }

        assert states.get(ma_ghe_1) == "KHOA"
        assert states.get(ma_ghe_2) == "KHOA"

        chup_anh(
            driver,
            "TC25_khoa_cap_ghe_doi.png",
        )

        print(
            f"PASS Bước 3: Cả cặp {pair_label} chuyển sang KHOA"
        )

        cho(3)

    finally:
        for ma_ghe in [
            ma_ghe_1,
            ma_ghe_2,
        ]:
            if ma_ghe:
                api_request(
                    "PUT",
                    f"/ghes/{ma_ghe}",
                    {
                        "trangThai": "HOAT_DONG",
                    },
                    token=admin["token"],
                )


# ==========================================================
# TC35 - CẬP NHẬT TRẠNG THÁI PHÒNG CHIẾU
# FR-41
# ==========================================================
def test_TC35_cap_nhat_trang_thai_phong(driver, admin):
    print("\n========================================")
    print("TC35 - CẬP NHẬT TRẠNG THÁI PHÒNG CHIẾU")
    print("========================================")

    status, rooms_response = api_request(
        "GET",
        "/phong-chieus?trangThai=HOAT_DONG",
    )

    assert status == 200

    rooms = rooms_response.get("data", [])

    if not rooms:
        pytest.skip(
            "Không có phòng HOAT_DONG để kiểm thử."
        )

    room = rooms[0]
    ma_phong = room["maPhong"]
    ten_phong = room["tenPhong"]

    changed = False

    try:
        # BƯỚC 1
        print("Bước 1: Mở Quản lý phòng chiếu")

        mo_trang_phong(
            driver,
            admin,
        )

        print(
            "PASS Bước 1: Danh sách phòng hiển thị"
        )

        # BƯỚC 2
        print(
            f"Bước 2: Chọn chỉnh sửa phòng {ma_phong}"
        )

        chon_phong(
            driver,
            ma_phong,
        )

        driver.find_element(
            By.XPATH,
            "//button[normalize-space()='Chỉnh sửa phòng']",
        ).click()

        cho()

        modal = WebDriverWait(
            driver,
            10,
        ).until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".pcm-modal-backdrop",
                )
            )
        )

        assert modal.is_displayed()

        print(
            "PASS Bước 2: Form chỉnh sửa phòng hiển thị"
        )

        # BƯỚC 3
        print(
            "Bước 3: Đổi trạng thái sang NGUNG_HOAT_DONG"
        )

        status_select = modal.find_element(
            By.XPATH,
            ".//div[contains(@class,'pcm-field') "
            "and label[normalize-space()='Trạng thái']]//select",
        )

        Select(
            status_select
        ).select_by_value(
            "NGUNG_HOAT_DONG"
        )

        cho()

        assert (
            status_select.get_attribute("value")
            == "NGUNG_HOAT_DONG"
        )

        print(
            "PASS Bước 3: Hệ thống chấp nhận trạng thái hợp lệ"
        )

        # BƯỚC 4
        print("Bước 4: Lưu thay đổi")

        modal.find_element(
            By.XPATH,
            ".//button[normalize-space()='Lưu thay đổi']",
        ).click()

        cho()

        success = WebDriverWait(
            driver,
            10,
        ).until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".pcm-alert-success",
                )
            )
        )

        assert (
            "Cập nhật phòng chiếu thành công"
            in success.text
        )

        changed = True

        card = next(
            item
            for item in driver.find_elements(
                By.CSS_SELECTOR,
                ".pcm-room-card",
            )
            if ma_phong in item.text
        )

        assert (
            "Ngừng hoạt động"
            in card.text
        )

        chup_anh(
            driver,
            "TC35_cap_nhat_trang_thai_phong.png",
        )

        print(
            "PASS Bước 4: Trạng thái phòng được cập nhật trên danh sách"
        )

        cho(3)

    finally:
        if changed:
            api_request(
                "PUT",
                f"/phong-chieus/{ma_phong}",
                {
                    "tenPhong": ten_phong,
                    "trangThai": "HOAT_DONG",
                },
                token=admin["token"],
            )
