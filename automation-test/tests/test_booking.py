import json
import os
import time
import urllib.error
import urllib.request

import pytest
from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


BASE_URL = "http://127.0.0.1:5173"
API_URL = "http://127.0.0.1:8000/api"

CUSTOMER_EMAIL = "nguyenan@gmail.com"
CUSTOMER_PASSWORD = "123456"

EVIDENCE_DIR = "evidence"
os.makedirs(EVIDENCE_DIR, exist_ok=True)

# Thời gian dừng giữa các thao tác để dễ quan sát khi chạy Selenium.
ACTION_DELAY = 2.0

def cho(seconds=ACTION_DELAY):
    time.sleep(seconds)


# ==========================================================
# ĐĂNG NHẬP KHÁCH HÀNG BẰNG API
# - Chỉ gọi 1 lần cho cả file
# - Không phụ thuộc tốc độ cập nhật state của React
# ==========================================================
def dang_nhap_khach_hang_api():
    payload = json.dumps({
        "identifier": CUSTOMER_EMAIL,
        "matKhau": CUSTOMER_PASSWORD,
    }).encode("utf-8")

    request = urllib.request.Request(
        f"{API_URL}/auth/customer/login",
        data=payload,
        headers={
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
        method="POST",
    )

    try:
        with urllib.request.urlopen(request, timeout=15) as response:
            body = json.loads(
                response.read().decode("utf-8")
            )

            if response.status != 200:
                pytest.fail(
                    f"Đăng nhập khách hàng thất bại. HTTP {response.status}: {body}"
                )

            return body

    except urllib.error.HTTPError as error:
        try:
            body = json.loads(
                error.read().decode("utf-8")
            )
        except Exception:
            body = {}

        message = body.get(
            "message",
            str(error),
        )

        if error.code == 429:
            pytest.fail(
                "API đăng nhập khách hàng đang bị giới hạn 5 lần/phút "
                "(HTTP 429). Chờ khoảng 1 phút rồi chạy lại."
            )

        pytest.fail(
            f"Không đăng nhập được tài khoản khách hàng. "
            f"HTTP {error.code}: {message}"
        )

    except Exception as error:
        pytest.fail(
            "Không kết nối được API backend tại "
            f"{API_URL}. Lỗi: {error}"
        )


@pytest.fixture(scope="module")
def driver():
    login_data = dang_nhap_khach_hang_api()

    token = login_data.get("token")
    tai_khoan = login_data.get("taiKhoan", {})
    khach_hang = login_data.get("khachHang")

    if not token:
        pytest.fail(
            "API đăng nhập thành công nhưng không trả về token."
        )

    browser = webdriver.Chrome()
    browser.maximize_window()

    # Phải mở cùng origin trước khi ghi localStorage
    browser.get(f"{BASE_URL}/home")
    cho()

    WebDriverWait(browser, 10).until(
        lambda d: d.execute_script(
            "return document.readyState"
        ) == "complete"
    )

    browser.execute_script(
        """
        localStorage.setItem('token', arguments[0]);
        localStorage.setItem('vaiTro', arguments[1]);
        localStorage.setItem('taiKhoan', arguments[2]);

        if (arguments[3]) {
            localStorage.setItem('khachHang', arguments[3]);
        }
        """,
        token,
        tai_khoan.get("vaiTro", "KHACH_HANG"),
        json.dumps(tai_khoan, ensure_ascii=False),
        (
            json.dumps(khach_hang, ensure_ascii=False)
            if khach_hang
            else None
        ),
    )

    role = browser.execute_script(
        "return localStorage.getItem('vaiTro');"
    )

    assert role == "KHACH_HANG"

    yield browser

    browser.quit()


def chup_anh(driver, ten_file):
    driver.save_screenshot(
        os.path.join(EVIDENCE_DIR, ten_file)
    )


def mo_trang_phim(driver):
    driver.get(f"{BASE_URL}/movies")
    cho()

    WebDriverWait(driver, 15).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".movie-card"
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CLASS_NAME,
                    "movies-error"
                )
            ) > 0
    )

    errors = driver.find_elements(
        By.CLASS_NAME,
        "movies-error"
    )

    if errors and errors[0].is_displayed():
        pytest.fail(
            "Không tải được danh sách phim: "
            + errors[0].text
        )

    # Trang /movies mặc định đang ở tab PHIM ĐANG CHIẾU.
    # Dữ liệu seed hiện tại có các suất tháng 09/2026 đã qua,
    # còn suất tương lai 18/12/2026 thuộc phim SAP_CHIEU.
    # Vì vậy phải chuyển sang TẤT CẢ PHIM để tìm được phim có suất hợp lệ.
    all_buttons = driver.find_elements(
        By.XPATH,
        "//button[contains(normalize-space(.), 'TẤT CẢ PHIM')]"
    )

    if all_buttons:
        cho()
        driver.execute_script(
            "arguments[0].click();",
            all_buttons[0]
        )
        cho()

        WebDriverWait(driver, 5).until(
            lambda d:
                len(
                    d.find_elements(
                        By.CSS_SELECTOR,
                        ".movie-card"
                    )
                ) > 0
        )


def mo_suat_chieu_dau_tien(driver):
    """
    Tìm một phim đang có suất chiếu đang hoạt động.
    Khi tìm thấy, giữ trình duyệt tại /dat-ve/<maPhim>.
    """

    mo_trang_phim(driver)

    cards = driver.find_elements(
        By.CSS_SELECTOR,
        ".movie-card"
    )

    if not cards:
        pytest.skip(
            "Không có phim để kiểm thử."
        )

    for index in range(len(cards)):
        mo_trang_phim(driver)

        cards = driver.find_elements(
            By.CSS_SELECTOR,
            ".movie-card"
        )

        if index >= len(cards):
            break

        buttons = cards[index].find_elements(
            By.CSS_SELECTOR,
            ".buy-ticket-button"
        )

        if not buttons:
            continue

        cho()
        driver.execute_script(
            "arguments[0].click();",
            buttons[0]
        )
        cho()

        try:
            # Chờ trang /dat-ve/... tải XONG dữ liệu.
            # Không coi dòng "Đang tải suất chiếu..." là kết quả cuối cùng.
            WebDriverWait(driver, 15).until(
                lambda d:
                    "/dat-ve/" in d.current_url
                    and (
                        len(
                            d.find_elements(
                                By.CSS_SELECTOR,
                                ".showtime-button"
                            )
                        ) > 0
                        or any(
                            "Phim hiện chưa có suất chiếu"
                            in item.text
                            for item in d.find_elements(
                                By.CLASS_NAME,
                                "showtimes-message"
                            )
                            if item.is_displayed()
                        )
                        or len(
                            d.find_elements(
                                By.CLASS_NAME,
                                "showtimes-error"
                            )
                        ) > 0
                    )
            )
        except TimeoutException:
            continue

        errors = driver.find_elements(
            By.CLASS_NAME,
            "showtimes-error"
        )

        if errors and errors[0].is_displayed():
            continue

        showtime_buttons = driver.find_elements(
            By.CSS_SELECTOR,
            ".showtime-button"
        )

        if showtime_buttons:
            return

    pytest.skip(
        "Không tìm thấy phim nào có suất chiếu đang hoạt động."
    )


def mo_so_do_ghe_dau_tien(driver):
    mo_suat_chieu_dau_tien(driver)

    showtime_buttons = driver.find_elements(
        By.CSS_SELECTOR,
        ".showtime-button"
    )

    if not showtime_buttons:
        pytest.skip(
            "Không có suất chiếu để mở sơ đồ ghế."
        )

    cho()
    driver.execute_script(
        "arguments[0].click();",
        showtime_buttons[0]
    )
    cho()

    WebDriverWait(driver, 12).until(
        lambda d:
            "/chon-ghe/" in d.current_url
            and (
                len(
                    d.find_elements(
                        By.CSS_SELECTOR,
                        ".seat-rows"
                    )
                ) > 0
                or len(
                    d.find_elements(
                        By.CSS_SELECTOR,
                        ".seat-message.seat-error"
                    )
                ) > 0
            )
    )

    errors = driver.find_elements(
        By.CSS_SELECTOR,
        ".seat-message.seat-error"
    )

    if errors and errors[0].is_displayed():
        pytest.fail(
            "Không mở được sơ đồ ghế: "
            + errors[0].text
        )


def lay_tong_tien(driver):
    element = WebDriverWait(
        driver,
        10
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".seat-footer-total b"
            )
        )
    )

    digits = "".join(
        char
        for char in element.text
        if char.isdigit()
    )

    return int(digits or 0)


# ==========================================================
# TC06 - CHỌN SUẤT CHIẾU VÀ PHÒNG CHIẾU HỢP LỆ
# FR-20
# ==========================================================
def test_TC06_chon_suat_chieu_hop_le(driver):
    print("\n========================================")
    print("TC06 - CHỌN SUẤT CHIẾU HỢP LỆ")
    print("========================================")

    # BƯỚC 1
    print("Bước 1: Mở chi tiết phim")

    mo_suat_chieu_dau_tien(driver)

    assert "/dat-ve/" in driver.current_url

    print(
        "PASS Bước 1: Hiển thị thông tin phim và các suất chiếu"
    )

    # BƯỚC 2
    print("Bước 2: Chọn ngày chiếu")

    date_buttons = driver.find_elements(
        By.CSS_SELECTOR,
        ".showtimes-dates button"
    )

    assert date_buttons, (
        "Không có ngày chiếu để chọn."
    )

    cho()
    driver.execute_script(
        "arguments[0].click();",
        date_buttons[0]
    )
    cho()

    WebDriverWait(driver, 5).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".showtime-button"
                )
            ) > 0
    )

    print(
        "PASS Bước 2: Hiển thị danh sách suất chiếu trong ngày"
    )

    # BƯỚC 3
    print("Bước 3: Chọn suất chiếu")

    showtime_buttons = driver.find_elements(
        By.CSS_SELECTOR,
        ".showtime-button"
    )

    assert showtime_buttons, (
        "Không có suất chiếu đang hoạt động."
    )

    assert showtime_buttons[0].text.strip() != ""

    print(
        "PASS Bước 3: Ghi nhận suất chiếu đã chọn"
    )

    # BƯỚC 4
    print("Bước 4: Chuyển sang chọn ghế")

    cho()
    driver.execute_script(
        "arguments[0].click();",
        showtime_buttons[0]
    )
    cho()

    WebDriverWait(driver, 12).until(
        EC.url_contains("/chon-ghe/")
    )

    WebDriverWait(driver, 12).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".seat-rows"
            )
        )
    )

    assert "/chon-ghe/" in driver.current_url

    chup_anh(
        driver,
        "TC06_mo_so_do_ghe.png"
    )

    print(
        "PASS Bước 4: Hiển thị đúng sơ đồ ghế"
    )

    cho(3)


# ==========================================================
# TC07 - CHỌN GHẾ THƯỜNG ĐANG HOẠT ĐỘNG
# FR-21
# ==========================================================
def test_TC07_chon_ghe_thuong(driver):
    print("\n========================================")
    print("TC07 - CHỌN GHẾ THƯỜNG")
    print("========================================")

    # BƯỚC 1
    print("Bước 1: Mở sơ đồ ghế")

    mo_so_do_ghe_dau_tien(driver)

    print(
        "PASS Bước 1: Sơ đồ ghế hiển thị"
    )

    # BƯỚC 2
    print("Bước 2: Chọn ghế thường")

    seats = driver.find_elements(
        By.CSS_SELECTOR,
        "button.seat.thuong.free:not([disabled])"
    )

    if not seats:
        pytest.skip(
            "Không có ghế thường HOAT_DONG để kiểm thử."
        )

    seat = seats[0]

    cho()
    driver.execute_script(
        "arguments[0].click();",
        seat
    )
    cho()

    WebDriverWait(driver, 5).until(
        lambda d:
            "selected"
            in seat.get_attribute("class")
    )

    assert (
        "selected"
        in seat.get_attribute("class")
    )

    print(
        "PASS Bước 2: Ghế thường chuyển sang trạng thái đang chọn"
    )

    # BƯỚC 3
    print("Bước 3: Kiểm tra tổng tiền")

    total = lay_tong_tien(driver)

    assert total > 0

    chup_anh(
        driver,
        "TC07_chon_ghe_thuong.png"
    )

    print(
        f"PASS Bước 3: Tổng tiền = {total:,} đ"
    )

    cho(3)


# ==========================================================
# TC08 - CHỌN GHẾ VIP
# FR-21
# ==========================================================
def test_TC08_chon_ghe_vip(driver):
    print("\n========================================")
    print("TC08 - CHỌN GHẾ VIP")
    print("========================================")

    # BƯỚC 1
    print("Bước 1: Mở sơ đồ ghế")

    mo_so_do_ghe_dau_tien(driver)

    print(
        "PASS Bước 1: Sơ đồ ghế hiển thị theo loại"
    )

    # BƯỚC 2
    print("Bước 2: Chọn ghế VIP")

    seats = driver.find_elements(
        By.CSS_SELECTOR,
        "button.seat.vip.free:not([disabled])"
    )

    if not seats:
        pytest.skip(
            "Không có ghế VIP HOAT_DONG để kiểm thử."
        )

    seat = seats[0]

    cho()
    driver.execute_script(
        "arguments[0].click();",
        seat
    )
    cho()

    WebDriverWait(driver, 5).until(
        lambda d:
            "selected"
            in seat.get_attribute("class")
    )

    assert (
        "selected"
        in seat.get_attribute("class")
    )

    print(
        "PASS Bước 2: Ghế VIP được chọn"
    )

    # BƯỚC 3
    print("Bước 3: Kiểm tra giá VIP")

    total = lay_tong_tien(driver)

    assert total > 0

    chup_anh(
        driver,
        "TC08_chon_ghe_vip.png"
    )

    print(
        f"PASS Bước 3: Giá VIP được tính, tổng = {total:,} đ"
    )

    cho(3)


# ==========================================================
# TC09 - CHỌN GHẾ ĐÔI
# FR-21
# ==========================================================
def test_TC09_chon_ghe_doi(driver):
    print("\n========================================")
    print("TC09 - CHỌN GHẾ ĐÔI")
    print("========================================")

    # BƯỚC 1
    print("Bước 1: Mở sơ đồ ghế")

    mo_so_do_ghe_dau_tien(driver)

    print(
        "PASS Bước 1: Sơ đồ ghế hiển thị"
    )

    # BƯỚC 2
    print("Bước 2: Chọn một cặp ghế đôi")

    couple_seats = driver.find_elements(
        By.CSS_SELECTOR,
        "button.seat.doi.couple.free:not([disabled])"
    )

    if not couple_seats:
        pytest.skip(
            "Suất chiếu hiện tại không có cặp ghế đôi HOAT_DONG."
        )

    couple = couple_seats[0]
    label = couple.text.strip()

    assert "-" in label, (
        "Ghế đôi không được hiển thị theo cặp."
    )

    cho()
    driver.execute_script(
        "arguments[0].click();",
        couple
    )
    cho()

    WebDriverWait(driver, 5).until(
        lambda d:
            "selected"
            in couple.get_attribute("class")
    )

    assert (
        "selected"
        in couple.get_attribute("class")
    )

    print(
        f"PASS Bước 2: Cặp ghế {label} được chọn"
    )

    # BƯỚC 3
    print("Bước 3: Kiểm tra giá ghế đôi")

    total = lay_tong_tien(driver)

    assert total > 0

    chup_anh(
        driver,
        "TC09_chon_ghe_doi.png"
    )

    print(
        f"PASS Bước 3: Tổng tiền ghế đôi = {total:,} đ"
    )

    cho(3)


# ==========================================================
# TC10 - KHÔNG CHO CHỌN GHẾ ĐÃ BÁN
# FR-21
# ==========================================================
def test_TC10_khong_chon_ghe_da_ban(driver):
    print("\n========================================")
    print("TC10 - KHÔNG CHO CHỌN GHẾ ĐÃ BÁN")
    print("========================================")

    # BƯỚC 1
    print("Bước 1: Mở sơ đồ ghế")

    mo_so_do_ghe_dau_tien(driver)

    occupied = driver.find_elements(
        By.CSS_SELECTOR,
        "button.seat.occupied"
    )

    if not occupied:
        pytest.skip(
            "Dữ liệu hiện tại chưa có ghế đã bán/không khả dụng "
            "trong suất chiếu được chọn."
        )

    seat = occupied[0]

    print(
        "PASS Bước 1: Sơ đồ hiển thị ghế đã bán/không khả dụng"
    )

    # BƯỚC 2
    print("Bước 2: Kiểm tra ghế đã bán")

    assert seat.get_attribute("disabled") is not None

    seat_class = seat.get_attribute(
        "class"
    )

    assert "occupied" in seat_class
    assert "selected" not in seat_class

    chup_anh(
        driver,
        "TC10_ghe_da_ban.png"
    )

    print(
        "PASS Bước 2: Ghế đã bán bị disabled và không thể chọn"
    )

    cho(3)
