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
            payload = {
                "message": raw,
            }

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
    driver.get(
        f"{BASE_URL}/home"
    )
    cho(1)

    account = login_data.get(
        "taiKhoan",
        {},
    )

    driver.execute_script(
        """
        localStorage.clear();
        localStorage.setItem('token', arguments[0]);
        localStorage.setItem('vaiTro', arguments[1]);
        localStorage.setItem('taiKhoan', arguments[2]);
        """,
        login_data["token"],
        account.get(
            "vaiTro",
            "QUAN_LY",
        ),
        json.dumps(
            account,
            ensure_ascii=False,
        ),
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


def mo_trang_lich_chieu(
    driver,
    admin,
):
    dat_phien_admin(
        driver,
        admin,
    )

    driver.get(
        f"{BASE_URL}/dashboard/lich-chieu"
    )
    cho()

    WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Quản lý lịch chiếu']",
            )
        )
    )

    WebDriverWait(
        driver,
        15,
    ).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".schedule-table",
                )
            ) > 0
    )

    # Chờ danh mục phim/phòng nạp xong để dùng select.
    WebDriverWait(
        driver,
        15,
    ).until(
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


def set_native_value(
    driver,
    element,
    value,
):
    """
    Gán giá trị cho input date/time của React.
    Cách này tránh lỗi Chrome/Windows nhập ngày sai bằng send_keys().
    """

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
            new Event(
                'input',
                { bubbles: true }
            )
        );

        input.dispatchEvent(
            new Event(
                'change',
                { bubbles: true }
            )
        );
        """,
        element,
        value,
    )

    cho(0.6)

    assert (
        element.get_attribute("value")
        == value
    )


def lay_phim_va_phong():
    phim_status, phim_response = api_request(
        "GET",
        "/phims",
    )

    phong_status, phong_response = api_request(
        "GET",
        "/phong-chieus?trangThai=HOAT_DONG",
    )

    assert phim_status == 200
    assert phong_status == 200

    films = phim_response.get(
        "data",
        [],
    )

    rooms = phong_response.get(
        "data",
        [],
    )

    if not films:
        pytest.skip(
            "Hệ thống chưa có phim để kiểm thử lịch chiếu."
        )

    if not rooms:
        pytest.skip(
            "Hệ thống chưa có phòng HOAT_DONG để kiểm thử lịch chiếu."
        )

    return (
        films[0]["maPhim"],
        rooms[0]["maPhong"],
    )


def tim_ngay_test_trong(
    token,
    start_offset=0,
):
    """
    Chọn một ngày rất xa trong tương lai chưa có lịch chiếu.
    Tìm nhiều ngày để tránh xung đột với dữ liệu test cũ.
    """

    base = date(
        2098,
        1,
        1,
    ) + timedelta(
        days=start_offset
    )

    for index in range(0, 120):
        candidate = (
            base + timedelta(days=index)
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

        rows = response.get(
            "data",
            [],
        )

        active_rows = [
            item
            for item in rows
            if item.get("trangThai")
            == "HOAT_DONG"
        ]

        if not active_rows:
            return candidate

    pytest.fail(
        "Không tìm được ngày trống để tạo lịch chiếu kiểm thử."
    )


def nhap_form_lich(
    driver,
    ma_lich,
    ma_phim,
    ma_phong,
    ngay_chieu,
    gio_bat_dau,
    gio_ket_thuc,
    gia_ve=90000,
):
    code_input = driver.find_element(
        By.NAME,
        "maLichChieu",
    )

    code_input.clear()
    code_input.send_keys(
        ma_lich
    )
    cho(0.5)

    Select(
        driver.find_element(
            By.NAME,
            "maPhim",
        )
    ).select_by_value(
        ma_phim
    )
    cho(0.5)

    Select(
        driver.find_element(
            By.NAME,
            "maPhong",
        )
    ).select_by_value(
        ma_phong
    )
    cho(0.5)

    set_native_value(
        driver,
        driver.find_element(
            By.NAME,
            "ngayChieu",
        ),
        ngay_chieu,
    )

    set_native_value(
        driver,
        driver.find_element(
            By.NAME,
            "gioBatDau",
        ),
        gio_bat_dau,
    )

    set_native_value(
        driver,
        driver.find_element(
            By.NAME,
            "gioKetThuc",
        ),
        gio_ket_thuc,
    )

    price = driver.find_element(
        By.NAME,
        "giaVeCoBan",
    )

    price.clear()
    price.send_keys(
        str(gia_ve)
    )
    cho(0.5)


def bam_luu_lich(driver):
    button = WebDriverWait(
        driver,
        10,
    ).until(
        EC.element_to_be_clickable(
            (
                By.CSS_SELECTOR,
                "button.schedule-save",
            )
        )
    )

    cho()
    button.click()
    cho()


def loc_theo_ma(
    driver,
    ma_lich,
):
    search = driver.find_element(
        By.CSS_SELECTOR,
        ".schedule-filter input:not([type='date'])",
    )

    search.clear()
    search.send_keys(
        ma_lich
    )
    cho()

    driver.find_element(
        By.CSS_SELECTOR,
        ".filter-search",
    ).click()

    cho()

    WebDriverWait(
        driver,
        15,
    ).until(
        lambda d:
            "Đang tải..."
            not in d.find_element(
                By.CSS_SELECTOR,
                ".schedule-table-box",
            ).text
    )


def tim_row(
    driver,
    ma_lich,
):
    rows = driver.find_elements(
        By.CSS_SELECTOR,
        ".schedule-table tbody tr",
    )

    return next(
        (
            row
            for row in rows
            if ma_lich
            in row.text
        ),
        None,
    )


def tao_lich_api(
    token,
    ma_lich,
    ma_phim,
    ma_phong,
    ngay_chieu,
    gio_bat_dau,
    gio_ket_thuc,
    gia_ve=90000,
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
            "giaVeCoBan": gia_ve,
        },
        token=token,
    )


def huy_lich_api(
    token,
    ma_lich,
):
    return api_request(
        "DELETE",
        f"/lich-chieus/{ma_lich}",
        token=token,
    )


def tim_lich_hoat_dong_co_ve(
    token,
):
    """
    Tìm lịch HOAT_DONG có vé từ dữ liệu đơn hàng.
    Chỉ dùng đơn đã thanh toán / đã sử dụng để chắc chắn backend
    nhận diện là lịch đã phát sinh khách đặt vé.
    """

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

        orders = response.get(
            "data",
            [],
        )

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

                schedule_query = urllib.parse.urlencode(
                    {
                        "q": ma_lich,
                        "trangThai": "HOAT_DONG",
                        "page": 1,
                    }
                )

                schedule_status, schedule_response = api_request(
                    "GET",
                    f"/quan-ly/lich-chieus?{schedule_query}",
                    token=token,
                )

                if schedule_status != 200:
                    continue

                rows = schedule_response.get(
                    "data",
                    [],
                )

                exact = next(
                    (
                        row
                        for row in rows
                        if row.get("maLichChieu")
                        == ma_lich
                        and row.get("trangThai")
                        == "HOAT_DONG"
                    ),
                    None,
                )

                if exact:
                    return ma_lich

    return None


# ==========================================================
# TC19 - THÊM LỊCH CHIẾU HỢP LỆ
# FR-37
# ==========================================================
def test_TC19_them_lich_chieu_hop_le(
    driver,
    admin,
):
    print("\n========================================")
    print("TC19 - THÊM LỊCH CHIẾU HỢP LỆ")
    print("========================================")

    ma_phim, ma_phong = (
        lay_phim_va_phong()
    )

    ngay_chieu = tim_ngay_test_trong(
        admin["token"],
        start_offset=0,
    )

    ma_lich = (
        "LCAUTO"
        + uuid.uuid4().hex[:8].upper()
    )

    try:
        # BƯỚC 1
        print("Bước 1: Mở Quản lý lịch chiếu")

        mo_trang_lich_chieu(
            driver,
            admin,
        )

        assert (
            "Quản lý lịch chiếu"
            in driver.page_source
        )

        print(
            "PASS Bước 1: Trang quản lý lịch chiếu hiển thị"
        )

        # BƯỚC 2
        print("Bước 2: Nhập thông tin lịch hợp lệ")

        nhap_form_lich(
            driver,
            ma_lich,
            ma_phim,
            ma_phong,
            ngay_chieu,
            "08:00",
            "10:00",
            90000,
        )

        assert (
            driver.find_element(
                By.NAME,
                "maLichChieu",
            ).get_attribute("value")
            == ma_lich
        )

        print(
            "PASS Bước 2: Dữ liệu hợp lệ được nhập đầy đủ"
        )

        # BƯỚC 3
        print("Bước 3: Nhấn Lưu lịch chiếu")

        bam_luu_lich(
            driver
        )

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
            "Thêm lịch chiếu thành công"
            in success.text
        )

        loc_theo_ma(
            driver,
            ma_lich,
        )

        row = tim_row(
            driver,
            ma_lich,
        )

        assert row is not None

        chup_anh(
            driver,
            "TC19_them_lich_chieu_hop_le.png",
        )

        print(
            "PASS Bước 3: Lịch chiếu được thêm vào danh sách"
        )

        cho(3)

    finally:
        # Cleanup: chuyển lịch test sang NGUNG_HOAT_DONG.
        huy_lich_api(
            admin["token"],
            ma_lich,
        )


# ==========================================================
# TC20 - KHÔNG CHO THÊM LỊCH CHIẾU TRÙNG PHÒNG & THỜI GIAN
# FR-37
# ==========================================================
def test_TC20_khong_cho_lich_chieu_trung(
    driver,
    admin,
):
    print("\n========================================")
    print("TC20 - KHÔNG CHO LỊCH CHIẾU TRÙNG")
    print("========================================")

    ma_phim, ma_phong = (
        lay_phim_va_phong()
    )

    ngay_chieu = tim_ngay_test_trong(
        admin["token"],
        start_offset=30,
    )

    ma_lich_goc = (
        "LCBASE"
        + uuid.uuid4().hex[:8].upper()
    )

    ma_lich_trung = (
        "LCFAIL"
        + uuid.uuid4().hex[:8].upper()
    )

    # Tạo sẵn 1 lịch 10:00-13:00 để UI thử thêm lịch 12:00-15:00.
    status, response = tao_lich_api(
        admin["token"],
        ma_lich_goc,
        ma_phim,
        ma_phong,
        ngay_chieu,
        "10:00",
        "13:00",
        90000,
    )

    assert status == 201, (
        f"Không tạo được lịch nền cho TC20. "
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
            "PASS Bước 1: Trang thêm lịch hiển thị"
        )

        # BƯỚC 2
        print(
            "Bước 2: Nhập lịch 12:00-15:00 giao với lịch 10:00-13:00"
        )

        nhap_form_lich(
            driver,
            ma_lich_trung,
            ma_phim,
            ma_phong,
            ngay_chieu,
            "12:00",
            "15:00",
            90000,
        )

        print(
            "PASS Bước 2: Dữ liệu lịch giao thời gian được nhập"
        )

        # BƯỚC 3
        print("Bước 3: Nhấn Lưu lịch chiếu")

        bam_luu_lich(
            driver
        )

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
            "Phòng chiếu đã có lịch trong khoảng thời gian này."
        )

        assert expected in error.text

        # Đảm bảo lịch trùng không được tạo.
        query = urllib.parse.urlencode(
            {
                "q": ma_lich_trung,
                "page": 1,
            }
        )

        check_status, check_response = api_request(
            "GET",
            f"/quan-ly/lich-chieus?{query}",
            token=admin["token"],
        )

        assert check_status == 200

        assert not any(
            item.get("maLichChieu")
            == ma_lich_trung
            for item in check_response.get(
                "data",
                [],
            )
        )

        chup_anh(
            driver,
            "TC20_chan_lich_chieu_trung.png",
        )

        print(
            "PASS Bước 3: Hệ thống chặn lịch bị trùng phòng và thời gian"
        )

        cho(3)

    finally:
        huy_lich_api(
            admin["token"],
            ma_lich_goc,
        )


# ==========================================================
# TC21 - HỦY LỊCH CHIẾU CHƯA CÓ VÉ
# FR-39
# ==========================================================
def test_TC21_huy_lich_chieu_chua_co_ve(
    driver,
    admin,
):
    print("\n========================================")
    print("TC21 - HỦY LỊCH CHIẾU CHƯA CÓ VÉ")
    print("========================================")

    ma_phim, ma_phong = (
        lay_phim_va_phong()
    )

    ngay_chieu = tim_ngay_test_trong(
        admin["token"],
        start_offset=60,
    )

    ma_lich = (
        "LCCANCEL"
        + uuid.uuid4().hex[:8].upper()
    )

    status, response = tao_lich_api(
        admin["token"],
        ma_lich,
        ma_phim,
        ma_phong,
        ngay_chieu,
        "16:00",
        "18:00",
        90000,
    )

    assert status == 201, (
        f"Không tạo được lịch test TC21. "
        f"HTTP {status}: {response}"
    )

    try:
        # BƯỚC 1
        print("Bước 1: Mở danh sách lịch chiếu")

        mo_trang_lich_chieu(
            driver,
            admin,
        )

        print(
            "PASS Bước 1: Danh sách lịch chiếu hiển thị"
        )

        # BƯỚC 2
        print("Bước 2: Chọn lịch chưa có vé")

        loc_theo_ma(
            driver,
            ma_lich,
        )

        row = tim_row(
            driver,
            ma_lich,
        )

        assert row is not None

        cancel_button = row.find_element(
            By.CSS_SELECTOR,
            ".schedule-cancel",
        )

        assert cancel_button.is_enabled()

        print(
            "PASS Bước 2: Lịch đủ điều kiện hủy"
        )

        # BƯỚC 3
        print("Bước 3: Thực hiện Hủy")

        cho()

        driver.execute_script(
            "arguments[0].click();",
            cancel_button,
        )

        WebDriverWait(
            driver,
            5,
        ).until(
            EC.alert_is_present()
        )

        alert = driver.switch_to.alert

        assert ma_lich in alert.text

        cho(1)
        alert.accept()
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
            "Hủy lịch chiếu thành công"
            in success.text
        )

        row_after = WebDriverWait(
            driver,
            10,
        ).until(
            lambda d:
                tim_row(
                    d,
                    ma_lich,
                )
        )

        assert (
            "Đã hủy"
            in row_after.text
        )

        chup_anh(
            driver,
            "TC21_huy_lich_chua_co_ve.png",
        )

        print(
            "PASS Bước 3: Lịch được chuyển sang trạng thái Đã hủy"
        )

        cho(3)

    finally:
        # Gọi lại không gây hại; đảm bảo lịch không còn hoạt động nếu test dừng giữa chừng.
        huy_lich_api(
            admin["token"],
            ma_lich,
        )


# ==========================================================
# TC22 - KHÔNG CHO HỦY LỊCH CHIẾU ĐÃ CÓ VÉ
# FR-39
# ==========================================================
def test_TC22_khong_cho_huy_lich_da_co_ve(
    driver,
    admin,
):
    print("\n========================================")
    print("TC22 - KHÔNG CHO HỦY LỊCH ĐÃ CÓ VÉ")
    print("========================================")

    ma_lich = tim_lich_hoat_dong_co_ve(
        admin["token"]
    )

    if not ma_lich:
        pytest.skip(
            "Hiện chưa có lịch HOAT_DONG đã phát sinh vé "
            "để kiểm thử TC22."
        )

    # BƯỚC 1
    print("Bước 1: Mở danh sách lịch chiếu")

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

    loc_theo_ma(
        driver,
        ma_lich,
    )

    row = tim_row(
        driver,
        ma_lich,
    )

    assert row is not None

    cancel_button = row.find_element(
        By.CSS_SELECTOR,
        ".schedule-cancel",
    )

    print(
        "PASS Bước 2: Hệ thống xác định lịch đã phát sinh vé"
    )

    # BƯỚC 3
    print("Bước 3: Nhấn Hủy")

    cho()

    driver.execute_script(
        "arguments[0].click();",
        cancel_button,
    )

    WebDriverWait(
        driver,
        5,
    ).until(
        EC.alert_is_present()
    )

    alert = driver.switch_to.alert
    cho(1)
    alert.accept()
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
        "Không thể hủy do đã có vé được bán."
    )

    assert expected in error.text

    # Lịch vẫn phải còn HOAT_DONG.
    query = urllib.parse.urlencode(
        {
            "q": ma_lich,
            "trangThai": "HOAT_DONG",
            "page": 1,
        }
    )

    status, response = api_request(
        "GET",
        f"/quan-ly/lich-chieus?{query}",
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
        "TC22_chan_huy_lich_da_co_ve.png",
    )

    print(
        "PASS Bước 3: Hệ thống không cho hủy lịch đã có vé"
    )

    cho(3)
