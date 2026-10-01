import json
import os
import time
import uuid
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
    headers = {"Accept": "application/json"}

    if data is not None:
        body = json.dumps(data).encode("utf-8")
        headers["Content-Type"] = "application/json"

    if token:
        headers["Authorization"] = f"Bearer {token}"

    req = urllib.request.Request(
        f"{API_URL}{path}",
        data=body,
        headers=headers,
        method=method,
    )

    try:
        with urllib.request.urlopen(req, timeout=20) as response:
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


def mo_trang_san_pham(driver, admin):
    dat_phien_admin(driver, admin)

    driver.get(
        f"{BASE_URL}/dashboard/san-pham"
    )
    cho()

    WebDriverWait(driver, 15).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Quản lý sản phẩm']",
            )
        )
    )

    WebDriverWait(driver, 15).until(
        lambda d:
            "Đang tải sản phẩm..."
            not in d.find_element(
                By.CSS_SELECTOR,
                ".combo-admin-table"
            ).text
    )


def mo_form_them_san_pham(driver):
    button = WebDriverWait(
        driver,
        10,
    ).until(
        EC.element_to_be_clickable(
            (
                By.XPATH,
                "//button[contains(normalize-space(.),'Thêm sản phẩm')]",
            )
        )
    )

    cho()
    button.click()
    cho()

    dialog = WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                "dialog.combo-admin-dialog",
            )
        )
    )

    return dialog


def nhap_form_san_pham(
    dialog,
    ma_sp,
    ten_sp,
    gia,
    hinh_anh="https://picsum.photos/300/400",
    mo_ta="Sản phẩm phục vụ kiểm thử tự động Selenium.",
):
    # Dùng label để tìm đúng ô thay vì input[type='text'].
    # Trong React, ô Mã SP và Tên SP không khai báo type="text"
    # nên CSS input[type='text'] sẽ không tìm thấy chúng.
    code_input = dialog.find_element(
        By.XPATH,
        ".//label[contains(normalize-space(.),'Mã sản phẩm')]//input",
    )

    name_input = dialog.find_element(
        By.XPATH,
        ".//label[contains(normalize-space(.),'Tên sản phẩm')]//input",
    )

    type_select = dialog.find_element(
        By.XPATH,
        ".//label[contains(normalize-space(.),'Loại sản phẩm')]//select",
    )

    price_input = dialog.find_element(
        By.XPATH,
        ".//label[contains(normalize-space(.),'Giá bán')]//input",
    )

    image_input = dialog.find_element(
        By.XPATH,
        ".//label[contains(normalize-space(.),'Link ảnh sản phẩm')]//input",
    )

    textarea = dialog.find_element(
        By.XPATH,
        ".//label[contains(normalize-space(.),'Mô tả')]//textarea",
    )

    code_input.clear()
    code_input.send_keys(ma_sp)
    cho(0.5)

    name_input.clear()
    name_input.send_keys(ten_sp)
    cho(0.5)

    Select(type_select).select_by_value("BAP")
    cho(0.5)

    price_input.clear()
    price_input.send_keys(str(gia))
    cho(0.5)

    image_input.clear()
    image_input.send_keys(hinh_anh)
    cho(0.5)

    textarea.clear()
    textarea.send_keys(mo_ta)
    cho(0.5)


def bam_luu_san_pham(driver):
    button = WebDriverWait(
        driver,
        10,
    ).until(
        EC.element_to_be_clickable(
            (
                By.XPATH,
                "//dialog//form//footer//button"
                "[normalize-space()='Thêm sản phẩm']",
            )
        )
    )

    cho()
    button.click()
    cho()


# ==========================================================
# TC26 - THÊM SẢN PHẨM HỢP LỆ
# FR-45
# ==========================================================
def test_TC26_them_san_pham_hop_le(driver, admin):
    print("\n========================================")
    print("TC26 - THÊM SẢN PHẨM HỢP LỆ")
    print("========================================")

    ma_sp = "SPAUTO" + uuid.uuid4().hex[:6].upper()
    ten_sp = "Bắp test Selenium " + uuid.uuid4().hex[:4]

    try:
        # BƯỚC 1
        print("Bước 1: Mở Quản lý sản phẩm")

        mo_trang_san_pham(
            driver,
            admin,
        )

        assert "Quản lý sản phẩm" in driver.page_source

        print(
            "PASS Bước 1: Danh sách sản phẩm hiển thị"
        )

        # BƯỚC 2
        print("Bước 2: Chọn Thêm sản phẩm")

        dialog = mo_form_them_san_pham(
            driver
        )

        assert dialog.is_displayed()

        print(
            "PASS Bước 2: Form thêm sản phẩm hiển thị"
        )

        # BƯỚC 3
        print("Bước 3: Nhập dữ liệu hợp lệ")

        nhap_form_san_pham(
            dialog,
            ma_sp,
            ten_sp,
            35000,
        )

        print(
            "PASS Bước 3: Dữ liệu được nhập đầy đủ"
        )

        # BƯỚC 4
        print("Bước 4: Nhấn Thêm sản phẩm")

        bam_luu_san_pham(driver)

        success = WebDriverWait(
            driver,
            15,
        ).until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".combo-admin-success",
                )
            )
        )

        assert (
            "Thêm sản phẩm thành công"
            in success.text
        )

        # Tìm lại sản phẩm vừa tạo để xác nhận tồn tại trong danh sách.
        search = driver.find_element(
            By.CSS_SELECTOR,
            "input[aria-label='Tìm sản phẩm']",
        )

        search.clear()
        search.send_keys(ten_sp)
        cho()

        driver.find_element(
            By.XPATH,
            "//form[contains(@class,'combo-admin-filters')]"
            "//button[normalize-space()='Tìm kiếm']",
        ).click()

        cho()

        WebDriverWait(driver, 10).until(
            lambda d:
                ten_sp
                in d.find_element(
                    By.CSS_SELECTOR,
                    ".combo-admin-table",
                ).text
        )

        chup_anh(
            driver,
            "TC26_them_san_pham_hop_le.png",
        )

        print(
            "PASS Bước 4: Thêm sản phẩm thành công và hiển thị trong danh sách"
        )

        cho(3)

    finally:
        # Không xóa cứng vì hệ thống dùng thao tác "Ngừng bán".
        # Cleanup qua API để sản phẩm test không còn hoạt động.
        api_request(
            "DELETE",
            f"/san-phams/{ma_sp}",
            token=admin["token"],
        )


# ==========================================================
# TC27 - KHÔNG CHO THÊM SẢN PHẨM CÓ GIÁ KHÔNG HỢP LỆ
# FR-45
# ==========================================================
def test_TC27_khong_them_san_pham_gia_am(driver, admin):
    print("\n========================================")
    print("TC27 - KHÔNG CHO THÊM SẢN PHẨM GIÁ ÂM")
    print("========================================")

    ma_sp = "SPFAIL" + uuid.uuid4().hex[:6].upper()
    ten_sp = "Sản phẩm giá âm test"

    # BƯỚC 1
    print("Bước 1: Mở form Thêm sản phẩm")

    mo_trang_san_pham(
        driver,
        admin,
    )

    dialog = mo_form_them_san_pham(
        driver
    )

    assert dialog.is_displayed()

    print(
        "PASS Bước 1: Form thêm sản phẩm hiển thị"
    )

    # BƯỚC 2
    print("Bước 2: Nhập đơn giá âm")

    nhap_form_san_pham(
        dialog,
        ma_sp,
        ten_sp,
        -10000,
    )

    price = dialog.find_element(
        By.CSS_SELECTOR,
        "input[type='number']",
    )

    assert price.get_attribute("value") == "-10000"

    print(
        "PASS Bước 2: Hệ thống ghi nhận dữ liệu nhập để kiểm tra"
    )

    # BƯỚC 3
    print("Bước 3: Nhấn Thêm sản phẩm")

    bam_luu_san_pham(driver)

    error = WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                "dialog .combo-admin-error",
            )
        )
    )

    assert error.text.strip() != ""

    assert (
        "nhỏ hơn 0" in error.text.lower()
        or "giá bán" in error.text.lower()
    )

    # Dialog vẫn mở -> không tạo thành công.
    dialog_after = driver.find_element(
        By.CSS_SELECTOR,
        "dialog.combo-admin-dialog",
    )

    assert dialog_after.is_displayed()

    chup_anh(
        driver,
        "TC27_khong_them_gia_am.png",
    )

    print(
        "PASS Bước 3: Hệ thống từ chối lưu sản phẩm có giá âm"
    )

    cho(3)


# ==========================================================
# TC28 - TRA CỨU SẢN PHẨM
# FR-47
# ==========================================================
def test_TC28_tra_cuu_san_pham(driver, admin):
    print("\n========================================")
    print("TC28 - TRA CỨU SẢN PHẨM")
    print("========================================")

    # Lấy một sản phẩm có thật để test không phụ thuộc dữ liệu cố định.
    status, products = api_request(
        "GET",
        "/quan-ly/san-phams?page=1",
        token=admin["token"],
    )

    assert status == 200, (
        f"Không tải được dữ liệu sản phẩm. HTTP {status}"
    )

    data = products.get("data", [])

    if not data:
        pytest.skip(
            "Hệ thống hiện chưa có sản phẩm để kiểm tra tra cứu."
        )

    product = data[0]
    keyword = product["tenSP"]

    # BƯỚC 1
    print("Bước 1: Mở Quản lý sản phẩm")

    mo_trang_san_pham(
        driver,
        admin,
    )

    table = driver.find_element(
        By.CSS_SELECTOR,
        ".combo-admin-table",
    )

    assert table.is_displayed()

    print(
        "PASS Bước 1: Danh sách sản phẩm hiển thị"
    )

    # BƯỚC 2
    print(
        f"Bước 2: Nhập từ khóa '{keyword}'"
    )

    search = driver.find_element(
        By.CSS_SELECTOR,
        "input[aria-label='Tìm sản phẩm']",
    )

    search.clear()
    search.send_keys(keyword)
    cho()

    assert (
        search.get_attribute("value")
        == keyword
    )

    print(
        "PASS Bước 2: Hệ thống ghi nhận từ khóa"
    )

    # BƯỚC 3
    print("Bước 3: Nhấn Tìm kiếm")

    driver.find_element(
        By.XPATH,
        "//form[contains(@class,'combo-admin-filters')]"
        "//button[normalize-space()='Tìm kiếm']",
    ).click()

    cho()

    WebDriverWait(driver, 10).until(
        lambda d:
            "Đang tải sản phẩm..."
            not in d.find_element(
                By.CSS_SELECTOR,
                ".combo-admin-table",
            ).text
    )

    result_text = driver.find_element(
        By.CSS_SELECTOR,
        ".combo-admin-table",
    ).text

    assert keyword in result_text

    chup_anh(
        driver,
        "TC28_tra_cuu_san_pham.png",
    )

    print(
        "PASS Bước 3: Hiển thị đúng sản phẩm phù hợp"
    )

    cho(3)
