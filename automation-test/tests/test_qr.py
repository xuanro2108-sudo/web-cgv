import json
import os
import time
import urllib.error
import urllib.parse
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
    options = webdriver.ChromeOptions()

    # Cho phép camera giả để khi bấm "Quét QR" modal không bị đóng
    # ngay vì thiếu quyền camera trên máy kiểm thử.
    options.add_argument("--use-fake-ui-for-media-stream")
    options.add_argument("--use-fake-device-for-media-stream")

    browser = webdriver.Chrome(
        options=options
    )

    browser.maximize_window()

    yield browser

    browser.quit()


def tim_suat_va_ghe_trong():
    """
    Tìm một suất chiếu tương lai có ít nhất 1 ghế vật lý HOAT_DONG
    và chưa bị giữ/đặt trong JSON chi tiết lịch chiếu.
    """

    status, response = api_request(
        "GET",
        "/lich-chieus",
    )

    assert status == 200, (
        f"Không tải được lịch chiếu. HTTP {status}"
    )

    showtimes = response.get(
        "data",
        [],
    )

    for showtime in showtimes:
        ma_lich = showtime.get(
            "maLichChieu"
        )

        if not ma_lich:
            continue

        detail_status, detail_response = api_request(
            "GET",
            f"/lich-chieus/{ma_lich}",
        )

        if detail_status != 200:
            continue

        detail = detail_response.get(
            "data",
            {},
        )

        room = (
            detail.get("phong_chieu")
            or detail.get("phongChieu")
            or {}
        )

        seat_map = (
            room.get("so_do_ghe")
            or room.get("soDoGhe")
            or {}
        )

        seats = seat_map.get(
            "ghes",
            [],
        )

        free_seat = next(
            (
                seat
                for seat in seats
                if seat.get("trangThai")
                == "HOAT_DONG"
            ),
            None,
        )

        if free_seat:
            return (
                ma_lich,
                free_seat["maGhe"],
            )

    pytest.skip(
        "Không có suất chiếu tương lai còn ghế trống để tạo vé QR test."
    )


def tao_don_da_thanh_toan(token):
    """
    Tạo một đơn POS riêng cho TC16.
    Không dùng đơn thật của người dùng để tránh làm vé thật thành DA_SU_DUNG.
    """

    ma_lich, ma_ghe = (
        tim_suat_va_ghe_trong()
    )

    phone_suffix = (
        int(time.time() * 1000)
        % 100000000
    )

    phone = (
        "08"
        + f"{phone_suffix:08d}"
    )

    status, response = api_request(
        "POST",
        "/quan-ly/ban-ve-tai-quay",
        {
            "hoTen": "Khách QR Selenium",
            "soDienThoai": phone,
            "maLichChieu": ma_lich,
            "maGhes": [
                ma_ghe,
            ],
            "combos": [],
            "phuongThuc": "TIEN_MAT",
            "xacNhanNgay": True,
        },
        token=token,
    )

    assert status == 201, (
        f"Không tạo được đơn test cho TC16. "
        f"HTTP {status}: {response}"
    )

    order = response.get(
        "data",
        {},
    )

    assert (
        order.get("trangThai")
        == "DA_THANH_TOAN"
    )

    assert order.get("maDonHang")
    assert order.get("maQR")

    return order


def mo_quan_ly_don_hang(driver, admin):
    dat_phien_admin(
        driver,
        admin,
    )

    driver.get(
        f"{BASE_URL}/dashboard/don-hang"
    )
    cho()

    WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Quản lý đơn hàng & vé']",
            )
        )
    )


def tim_don_tren_giao_dien(
    driver,
    ma_don,
):
    status_select = driver.find_element(
        By.CSS_SELECTOR,
        ".om-filters select",
    )

    Select(
        status_select
    ).select_by_value("")

    search = driver.find_element(
        By.CSS_SELECTOR,
        ".om-search input",
    )

    search.clear()
    search.send_keys(
        ma_don
    )

    cho()

    row = WebDriverWait(
        driver,
        15,
    ).until(
        lambda d:
            next(
                (
                    item
                    for item in d.find_elements(
                        By.CSS_SELECTOR,
                        ".om-order-row",
                    )
                    if ma_don in item.text
                ),
                False,
            )
    )

    return row


# ==========================================================
# TC16 - QUÉT MÃ QR VÉ HỢP LỆ
# FR-31
# ==========================================================
def test_TC16_quet_ma_qr_ve_hop_le(
    driver,
    admin,
):
    print("\n========================================")
    print("TC16 - QUÉT MÃ QR VÉ HỢP LỆ")
    print("========================================")

    # Dữ liệu nền riêng cho testcase.
    order = tao_don_da_thanh_toan(
        admin["token"]
    )

    ma_don = order["maDonHang"]
    ma_qr = order["maQR"]

    # --------------------------------------------------
    # BƯỚC 1
    # --------------------------------------------------
    print("Bước 1: Mở chức năng soát vé")

    mo_quan_ly_don_hang(
        driver,
        admin,
    )

    qr_button = WebDriverWait(
        driver,
        10,
    ).until(
        EC.element_to_be_clickable(
            (
                By.CSS_SELECTOR,
                "button.om-qr-trigger",
            )
        )
    )

    assert (
        "Quét QR"
        in qr_button.text
    )

    cho()
    qr_button.click()
    cho()

    camera_modal = WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".om-camera-modal",
            )
        )
    )

    assert (
        "Đưa mã QR vào khung hình"
        in camera_modal.text
    )

    chup_anh(
        driver,
        "TC16_B1_man_hinh_quet_qr.png",
    )

    print(
        "PASS Bước 1: Màn hình quét QR hiển thị"
    )

    # Đóng camera sau khi xác nhận giao diện.
    driver.find_element(
        By.CSS_SELECTOR,
        ".om-camera-modal .om-close",
    ).click()

    cho()

    # --------------------------------------------------
    # BƯỚC 2
    # --------------------------------------------------
    print("Bước 2: Quét QR vé đã thanh toán và chưa sử dụng")

    # Selenium không đưa chuỗi QR trực tiếp vào camera vật lý.
    # Ta mô phỏng phần "camera đã giải mã được QR" bằng cách gửi đúng
    # maQR vào endpoint mà giao diện previewQr() sử dụng.
    preview_status, preview_response = api_request(
        "POST",
        "/quan-ly/don-hangs/xem-qr",
        {
            "qrData": ma_qr,
        },
        token=admin["token"],
    )

    assert preview_status == 200, (
        f"Không đọc được QR hợp lệ. "
        f"HTTP {preview_status}: {preview_response}"
    )

    preview_order = preview_response.get(
        "data",
        {},
    )

    assert (
        preview_order.get("maDonHang")
        == ma_don
    )

    assert (
        preview_order.get("trangThai")
        == "DA_THANH_TOAN"
    )

    row = tim_don_tren_giao_dien(
        driver,
        ma_don,
    )

    assert ma_don in row.text
    assert "Đã đặt" in row.text

    print(
        "PASS Bước 2: Hệ thống tìm thấy vé QR hợp lệ"
    )

    # --------------------------------------------------
    # BƯỚC 3
    # --------------------------------------------------
    print("Bước 3: Xác nhận soát vé")

    scan_status, scan_response = api_request(
        "POST",
        f"/quan-ly/don-hangs/{ma_don}/quet-ma",
        {},
        token=admin["token"],
    )

    assert scan_status == 200, (
        f"Soát vé thất bại. "
        f"HTTP {scan_status}: {scan_response}"
    )

    scanned_order = scan_response.get(
        "data",
        {},
    )

    assert (
        scanned_order.get("trangThai")
        == "DA_SU_DUNG"
    )

    assert (
        "Quét mã thành công"
        in scan_response.get(
            "message",
            "",
        )
    )

    # Tải lại UI để xác nhận trạng thái sau soát vé.
    driver.refresh()
    cho()

    WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Quản lý đơn hàng & vé']",
            )
        )
    )

    row_after = tim_don_tren_giao_dien(
        driver,
        ma_don,
    )

    assert (
        "Đã sử dụng"
        in row_after.text
    )

    chup_anh(
        driver,
        "TC16_B3_ve_da_su_dung.png",
    )

    print(
        f"PASS Bước 3: Đơn {ma_don} chuyển sang Đã sử dụng"
    )

    cho(3)
