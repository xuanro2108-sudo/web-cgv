import json
import os
import re
import time
import urllib.error
import urllib.parse
import urllib.request

import pytest
from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
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


def mo_ban_ve_tai_quay(driver, admin):
    dat_phien_admin(
        driver,
        admin,
    )

    driver.get(
        f"{BASE_URL}/dashboard/ban-ve-tai-quay"
    )
    cho()

    WebDriverWait(
        driver,
        20,
    ).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".counter-movie-card",
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".counter-alert-error",
                )
            ) > 0
    )

    errors = driver.find_elements(
        By.CSS_SELECTOR,
        ".counter-alert-error",
    )

    if (
        errors
        and errors[0].is_displayed()
    ):
        pytest.fail(
            "Không tải được dữ liệu bán vé tại quầy: "
            + errors[0].text
        )


def chon_phim_co_suat(driver):
    cards = driver.find_elements(
        By.CSS_SELECTOR,
        ".counter-movie-card",
    )

    card = next(
        (
            item
            for item in cards
            if "Chưa có suất"
            not in item.text
            and "suất chiếu"
            in item.text
        ),
        None,
    )

    if card is None:
        pytest.skip(
            "Không có phim nào có suất chiếu tương lai để bán tại quầy."
        )

    ten_phim = card.find_element(
        By.TAG_NAME,
        "h3",
    ).text.strip()

    cho()

    driver.execute_script(
        "arguments[0].click();",
        card,
    )

    WebDriverWait(
        driver,
        15,
    ).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".counter-showtime-card",
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".counter-no-dates",
                )
            ) > 0
    )

    return ten_phim


def chon_suat_dau_tien(driver):
    showtimes = driver.find_elements(
        By.CSS_SELECTOR,
        ".counter-showtime-card",
    )

    if not showtimes:
        pytest.skip(
            "Phim được chọn không có suất chiếu khả dụng."
        )

    showtime_text = showtimes[0].text

    cho()

    driver.execute_script(
        "arguments[0].click();",
        showtimes[0],
    )

    WebDriverWait(
        driver,
        15,
    ).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    "button.seat.free:not([disabled])",
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".counter-alert-error",
                )
            ) > 0
    )

    return showtime_text


def chon_mot_ghe(driver):
    seats = driver.find_elements(
        By.CSS_SELECTOR,
        "button.seat.free:not([disabled])",
    )

    if not seats:
        pytest.skip(
            "Suất chiếu hiện không còn ghế trống."
        )

    seat = seats[0]
    label = seat.text.strip()

    cho()

    driver.execute_script(
        "arguments[0].click();",
        seat,
    )

    WebDriverWait(
        driver,
        10,
    ).until(
        lambda d:
            "selected"
            in d.find_element(
                By.XPATH,
                f"//button[contains(@class,'seat') "
                f"and normalize-space(.)='{label}']",
            ).get_attribute("class")
    )

    return label


def sang_buoc_combo(driver):
    button = driver.find_element(
        By.XPATH,
        "//button[contains(normalize-space(.),'TIẾP TỤC: CHỌN COMBO')]",
    )

    cho()
    button.click()
    cho()

    WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h2[normalize-space()='Chọn Bỏng ngô & Nước uống']",
            )
        )
    )


def chon_mot_combo(driver):
    cards = driver.find_elements(
        By.CSS_SELECTOR,
        ".counter-combo-card",
    )

    if not cards:
        pytest.skip(
            "Hệ thống chưa có combo để kiểm thử TC17."
        )

    card = cards[0]

    combo_name = card.find_element(
        By.TAG_NAME,
        "h3",
    ).text.strip()

    plus_buttons = card.find_elements(
        By.CSS_SELECTOR,
        "button.qty-btn",
    )

    assert len(plus_buttons) >= 2

    cho()

    plus_buttons[-1].click()
    cho()

    qty = card.find_element(
        By.CSS_SELECTOR,
        ".qty-val",
    )

    assert qty.text.strip() == "1"

    return combo_name


def sang_buoc_thanh_toan(driver):
    buttons = driver.find_elements(
        By.XPATH,
        "//button[contains(normalize-space(.),'TIẾP TỤC THANH TOÁN') "
        "or contains(normalize-space(.),'Tiếp tục: Thanh toán')]",
    )

    assert buttons, (
        "Không tìm thấy nút chuyển sang thanh toán."
    )

    cho()

    buttons[-1].click()
    cho()

    WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h2[normalize-space()='Thanh toán & Thông tin khách hàng']",
            )
        )
    )


def tao_so_dien_thoai_test():
    suffix = int(
        time.time() * 1000
    ) % 100000000

    return (
        "09"
        + f"{suffix:08d}"
    )


# ==========================================================
# TC17 - BÁN VÉ VÀ COMBO TẠI QUẦY THÀNH CÔNG
# FR-32
# ==========================================================
def test_TC17_ban_ve_va_combo_tai_quay_thanh_cong(
    driver,
    admin,
):
    print("\n========================================")
    print("TC17 - BÁN VÉ VÀ COMBO TẠI QUẦY THÀNH CÔNG")
    print("========================================")

    # --------------------------------------------------
    # BƯỚC 1
    # --------------------------------------------------
    print("Bước 1: Mở chức năng Bán vé tại quầy")

    mo_ban_ve_tai_quay(
        driver,
        admin,
    )

    assert driver.find_elements(
        By.CSS_SELECTOR,
        ".counter-movie-card",
    )

    print(
        "PASS Bước 1: Quy trình bán vé tại quầy hiển thị"
    )

    # --------------------------------------------------
    # BƯỚC 2
    # --------------------------------------------------
    print("Bước 2: Chọn phim và suất chiếu")

    ten_phim = chon_phim_co_suat(
        driver
    )

    showtime_text = chon_suat_dau_tien(
        driver
    )

    assert driver.find_elements(
        By.CSS_SELECTOR,
        ".seat-grid-container",
    )

    print(
        f"PASS Bước 2: Đã chọn {ten_phim}; suất {showtime_text}"
    )

    # --------------------------------------------------
    # BƯỚC 3
    # --------------------------------------------------
    print("Bước 3: Chọn ghế còn trống")

    seat_label = chon_mot_ghe(
        driver
    )

    price = driver.find_element(
        By.CSS_SELECTOR,
        ".summary-price",
    )

    assert any(
        char.isdigit()
        for char in price.text
    )

    print(
        f"PASS Bước 3: Ghế {seat_label} được chọn và tính tiền"
    )

    # --------------------------------------------------
    # BƯỚC 4
    # --------------------------------------------------
    print("Bước 4: Chọn combo")

    sang_buoc_combo(
        driver
    )

    combo_name = chon_mot_combo(
        driver
    )

    print(
        f"PASS Bước 4: Đã thêm combo '{combo_name}' vào đơn"
    )

    # --------------------------------------------------
    # BƯỚC 5
    # --------------------------------------------------
    print("Bước 5: Thanh toán tiền mặt")

    sang_buoc_thanh_toan(
        driver
    )

    ho_ten = "Khách Test Selenium"
    so_dien_thoai = tao_so_dien_thoai_test()

    name_input = driver.find_element(
        By.CSS_SELECTOR,
        "input[placeholder='Nhập họ tên']",
    )

    phone_input = driver.find_element(
        By.CSS_SELECTOR,
        "input[placeholder='Nhập số điện thoại']",
    )

    name_input.clear()
    name_input.send_keys(
        ho_ten
    )
    cho(0.5)

    phone_input.clear()
    phone_input.send_keys(
        so_dien_thoai
    )
    cho(0.5)

    cash_radio = driver.find_element(
        By.CSS_SELECTOR,
        "input[name='phuongThuc'][value='TIEN_MAT']",
    )

    if not cash_radio.is_selected():
        cash_radio.click()

    cho()

    submit_button = driver.find_element(
        By.CSS_SELECTOR,
        ".counter-btn-submit-order",
    )

    assert submit_button.is_enabled()

    driver.execute_script(
        "arguments[0].click();",
        submit_button,
    )

    success = WebDriverWait(
        driver,
        20,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".counter-success-card",
            )
        )
    )

    assert (
        "Bán vé thành công!"
        in success.text
    )

    order_match = re.search(
        r"Mã đơn:\s*(DH[A-Z0-9]+)",
        success.text,
    )

    assert order_match, (
        "Không đọc được mã đơn sau khi bán vé."
    )

    ma_don = order_match.group(1)

    # Kiểm tra trực tiếp backend.
    status, response = api_request(
        "GET",
        f"/quan-ly/don-hangs/{ma_don}",
        token=admin["token"],
    )

    assert status == 200, (
        f"Không đọc được đơn vừa tạo. HTTP {status}"
    )

    order = response.get(
        "data",
        {},
    )

    assert (
        order.get("trangThai")
        == "DA_THANH_TOAN"
    )

    assert (
        order.get("kieuDat")
        == "TAI_QUAY"
    )

    tickets = (
        order.get("ve_ghes")
        or order.get("veGhes")
        or []
    )

    combos = (
        order.get("chi_tiet_combo_don_hangs")
        or order.get("chiTietComboDonHangs")
        or []
    )

    assert len(tickets) >= 1
    assert len(combos) >= 1

    chup_anh(
        driver,
        "TC17_ban_ve_tai_quay_thanh_cong.png",
    )

    print(
        f"PASS Bước 5: Đơn {ma_don} đã thanh toán thành công"
    )

    cho(3)
