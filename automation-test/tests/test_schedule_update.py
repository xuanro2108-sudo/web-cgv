import json
import os
import time
import uuid
import urllib.error
import urllib.parse
import urllib.request
from datetime import date, timedelta

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
    headers = {"Accept": "application/json"}

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


def mo_trang_lich_chieu(driver, admin):
    dat_phien_admin(driver, admin)

    driver.get(
        f"{BASE_URL}/dashboard/lich-chieu"
    )
    cho()

    WebDriverWait(driver, 15).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Quản lý lịch chiếu']",
            )
        )
    )

    WebDriverWait(driver, 15).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".schedule-table",
                )
            ) > 0
    )

    WebDriverWait(driver, 15).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    "select[name='maPhim'] option",
                )
            ) > 1
            and len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    "select[name='maPhong'] option",
                )
            ) > 1
    )


def set_native_value(driver, element, value):
    driver.execute_script(
        """
        const input = arguments[0];
        const value = arguments[1];

        const setter =
            Object.getOwnPropertyDescriptor(
                HTMLInputElement.prototype,
                'value'
            ).set;

        setter.call(input, value);

        input.dispatchEvent(
            new Event('input', { bubbles: true })
        );

        input.dispatchEvent(
            new Event('change', { bubbles: true })
        );
        """,
        element,
        value,
    )

    cho(0.7)

    assert (
        element.get_attribute("value")
        == value
    )


def lay_phim_va_phong():
    film_status, film_response = api_request(
        "GET",
        "/phims",
    )

    room_status, room_response = api_request(
        "GET",
        "/phong-chieus?trangThai=HOAT_DONG",
    )

    assert film_status == 200
    assert room_status == 200

    films = film_response.get("data", [])
    rooms = room_response.get("data", [])

    if not films:
        pytest.skip(
            "Hệ thống chưa có phim để kiểm thử."
        )

    if not rooms:
        pytest.skip(
            "Hệ thống chưa có phòng HOAT_DONG để kiểm thử."
        )

    return films, rooms


def tim_ngay_trong(token):
    base = date(2099, 1, 1)

    for offset in range(150):
        candidate = (
            base + timedelta(days=offset)
        ).isoformat()

        query = urllib.parse.urlencode(
            {
                "ngayChieu": candidate,
                "page": 1,
            }
        )

        status, response = api_request(
            "GET",
            f"/quan-ly/lich-chieus?{query}",
            token=token,
        )

        if status != 200:
            continue

        active = [
            item
            for item in response.get("data", [])
            if item.get("trangThai") == "HOAT_DONG"
        ]

        if not active:
            return candidate

    pytest.fail(
        "Không tìm được ngày trống để tạo lịch kiểm thử."
    )


def tao_lich_api(
    token,
    ma_lich,
    ma_phim,
    ma_phong,
    ngay_chieu,
    gio_bat_dau="08:00",
    gio_ket_thuc="10:00",
):
    return api_request(
        "POST",
        "/lich-chieus",
        {
            "maLichChieu": ma_lich,
            "maPhim": ma_phim,
            "maPhong": ma_phong,
            "ngayChieu": ngay_chieu,
            "gioBatDau": gio_bat_dau,
            "gioKetThuc": gio_ket_thuc,
            "giaVeCoBan": 90000,
        },
        token=token,
    )


def huy_lich_api(token, ma_lich):
    return api_request(
        "DELETE",
        f"/lich-chieus/{ma_lich}",
        token=token,
    )


def loc_ma_lich(driver, ma_lich):
    search = driver.find_element(
        By.CSS_SELECTOR,
        ".schedule-filter input:not([type='date'])",
    )

    search.clear()
    search.send_keys(ma_lich)
    cho()

    driver.find_element(
        By.CSS_SELECTOR,
        ".filter-search",
    ).click()

    WebDriverWait(driver, 15).until(
        lambda d:
            "Đang tải..."
            not in d.find_element(
                By.CSS_SELECTOR,
                ".schedule-table-box",
            ).text
    )

    cho()


def tim_row(driver, ma_lich):
    rows = driver.find_elements(
        By.CSS_SELECTOR,
        ".schedule-table tbody tr",
    )

    return next(
        (
            row
            for row in rows
            if ma_lich in row.text
        ),
        None,
    )


def tim_lich_hoat_dong_co_ve(token):
    for page in range(1, 11):
        query = urllib.parse.urlencode(
            {
                "q": "",
                "trangThai": "",
                "page": page,
            }
        )

        status, response = api_request(
            "GET",
            f"/quan-ly/don-hangs?{query}",
            token=token,
        )

        if status != 200:
            continue

        orders = response.get("data", [])

        if not orders:
            break

        for order in orders:
            if order.get("trangThai") not in {
                "DA_THANH_TOAN",
                "DA_SU_DUNG",
            }:
                continue

            tickets = (
                order.get("ve_ghes")
                or order.get("veGhes")
                or []
            )

            for ticket in tickets:
                schedule = (
                    ticket.get("lich_chieu")
                    or ticket.get("lichChieu")
                    or {}
                )

                ma_lich = (
                    schedule.get("maLichChieu")
                    or ticket.get("maLichChieu")
                )

                if not ma_lich:
                    continue

                q = urllib.parse.urlencode(
                    {
                        "q": ma_lich,
                        "trangThai": "HOAT_DONG",
                        "page": 1,
                    }
                )

                schedule_status, schedule_response = api_request(
                    "GET",
                    f"/quan-ly/lich-chieus?{q}",
                    token=token,
                )

                if schedule_status != 200:
                    continue

                match = next(
                    (
                        item
                        for item in schedule_response.get(
                            "data",
                            [],
                        )
                        if item.get("maLichChieu") == ma_lich
                        and item.get("trangThai")
                        == "HOAT_DONG"
                    ),
                    None,
                )

                if match:
                    return match

    return None


# ==========================================================
# TC36 - CẬP NHẬT LỊCH CHIẾU CHƯA CÓ KHÁCH ĐẶT VÉ
# FR-38
# ==========================================================
def test_TC36_cap_nhat_lich_chua_co_ve(driver, admin):
    print("\n========================================")
    print("TC36 - CẬP NHẬT LỊCH CHIẾU CHƯA CÓ VÉ")
    print("========================================")

    films, rooms = lay_phim_va_phong()

    ma_phim = films[0]["maPhim"]
    ma_phong = rooms[0]["maPhong"]
    ngay_chieu = tim_ngay_trong(
        admin["token"]
    )

    ma_lich = (
        "LCEDIT"
        + uuid.uuid4().hex[:8].upper()
    )

    status, response = tao_lich_api(
        admin["token"],
        ma_lich,
        ma_phim,
        ma_phong,
        ngay_chieu,
        "08:00",
        "10:00",
    )

    assert status == 201, (
        f"Không tạo được lịch nền TC36. "
        f"HTTP {status}: {response}"
    )

    try:
        # BƯỚC 1
        print("Bước 1: Mở Quản lý lịch chiếu")

        mo_trang_lich_chieu(
            driver,
            admin,
        )

        print(
            "PASS Bước 1: Danh sách lịch chiếu hiển thị"
        )

        # BƯỚC 2
        print("Bước 2: Chọn lịch chưa có vé")

        loc_ma_lich(
            driver,
            ma_lich,
        )

        row = tim_row(
            driver,
            ma_lich,
        )

        assert row is not None

        edit_button = row.find_element(
            By.CSS_SELECTOR,
            ".schedule-edit",
        )

        driver.execute_script(
            "arguments[0].click();",
            edit_button,
        )

        cho()

        heading = WebDriverWait(
            driver,
            10,
        ).until(
            EC.visibility_of_element_located(
                (
                    By.XPATH,
                    "//form[contains(@class,'schedule-form')]"
                    "//h2[normalize-space()='Cập nhật lịch chiếu']",
                )
            )
        )

        assert heading.is_displayed()

        assert (
            driver.find_element(
                By.NAME,
                "maLichChieu",
            ).get_attribute("value")
            == ma_lich
        )

        print(
            "PASS Bước 2: Form chỉnh sửa lịch chiếu hiển thị"
        )

        # BƯỚC 3
        print("Bước 3: Thay đổi giờ chiếu hợp lệ")

        set_native_value(
            driver,
            driver.find_element(
                By.NAME,
                "gioBatDau",
            ),
            "10:30",
        )

        set_native_value(
            driver,
            driver.find_element(
                By.NAME,
                "gioKetThuc",
            ),
            "12:30",
        )

        print(
            "PASS Bước 3: Dữ liệu mới vượt qua kiểm tra trên form"
        )

        # BƯỚC 4
        print("Bước 4: Lưu thay đổi")

        driver.find_element(
            By.CSS_SELECTOR,
            ".schedule-save",
        ).click()

        cho()

        success = WebDriverWait(
            driver,
            15,
        ).until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".schedule-success",
                )
            )
        )

        assert (
            "Cập nhật lịch chiếu thành công"
            in success.text
        )

        loc_ma_lich(
            driver,
            ma_lich,
        )

        row_after = tim_row(
            driver,
            ma_lich,
        )

        assert row_after is not None

        assert "10:30" in row_after.text
        assert "12:30" in row_after.text

        chup_anh(
            driver,
            "TC36_cap_nhat_lich_chua_co_ve.png",
        )

        print(
            "PASS Bước 4: Lịch chiếu được cập nhật thành công"
        )

        cho(3)

    finally:
        huy_lich_api(
            admin["token"],
            ma_lich,
        )


# ==========================================================
# TC37 - KHÔNG CHO CẬP NHẬT LỊCH ĐÃ CÓ KHÁCH ĐẶT VÉ
# FR-38
# ==========================================================
def test_TC37_khong_cap_nhat_lich_da_co_ve(driver, admin):
    print("\n========================================")
    print("TC37 - KHÔNG CẬP NHẬT LỊCH ĐÃ CÓ VÉ")
    print("========================================")

    schedule = tim_lich_hoat_dong_co_ve(
        admin["token"]
    )

    if not schedule:
        pytest.skip(
            "Chưa có lịch HOAT_DONG đã phát sinh vé để kiểm thử TC37."
        )

    ma_lich = schedule["maLichChieu"]

    # BƯỚC 1
    print("Bước 1: Mở Quản lý lịch chiếu")

    mo_trang_lich_chieu(
        driver,
        admin,
    )

    print(
        "PASS Bước 1: Danh sách lịch chiếu hiển thị"
    )

    # BƯỚC 2
    print(
        f"Bước 2: Chọn lịch đã có vé {ma_lich}"
    )

    loc_ma_lich(
        driver,
        ma_lich,
    )

    row = tim_row(
        driver,
        ma_lich,
    )

    assert row is not None

    edit_button = row.find_element(
        By.CSS_SELECTOR,
        ".schedule-edit",
    )

    driver.execute_script(
        "arguments[0].click();",
        edit_button,
    )

    cho()

    WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//form[contains(@class,'schedule-form')]"
                "//h2[normalize-space()='Cập nhật lịch chiếu']",
            )
        )
    )

    print(
        "PASS Bước 2: Hệ thống xác định và mở lịch đã phát sinh vé"
    )

    # BƯỚC 3
    print("Bước 3: Thay đổi thông tin và thực hiện cập nhật")

    room_select = driver.find_element(
        By.NAME,
        "maPhong",
    )

    current_room = room_select.get_attribute(
        "value"
    )

    options = [
        option.get_attribute("value")
        for option in Select(
            room_select
        ).options
        if option.get_attribute("value")
        and option.get_attribute("value")
        != current_room
    ]

    if options:
        Select(
            room_select
        ).select_by_value(
            options[0]
        )
    else:
        # Nếu chỉ có một phòng, đổi giá vé để vẫn phát sinh request update.
        price = driver.find_element(
            By.NAME,
            "giaVeCoBan",
        )

        old_price = int(
            float(
                price.get_attribute("value")
                or 0
            )
        )

        price.clear()
        price.send_keys(
            str(old_price + 1000)
        )

    cho()

    driver.find_element(
        By.CSS_SELECTOR,
        ".schedule-save",
    ).click()

    cho()

    error = WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".schedule-error",
            )
        )
    )

    expected = (
        "Không thể cập nhật vì lịch chiếu đã có khách hàng đặt vé."
    )

    assert expected in error.text

    # Kiểm tra backend vẫn còn lịch hoạt động.
    q = urllib.parse.urlencode(
        {
            "q": ma_lich,
            "trangThai": "HOAT_DONG",
            "page": 1,
        }
    )

    status, response = api_request(
        "GET",
        f"/quan-ly/lich-chieus?{q}",
        token=admin["token"],
    )

    assert status == 200

    assert any(
        item.get("maLichChieu")
        == ma_lich
        for item in response.get(
            "data",
            [],
        )
    )

    chup_anh(
        driver,
        "TC37_chan_cap_nhat_lich_da_co_ve.png",
    )

    print(
        "PASS Bước 3: Hệ thống chặn cập nhật lịch đã có khách đặt vé"
    )

    cho(3)
