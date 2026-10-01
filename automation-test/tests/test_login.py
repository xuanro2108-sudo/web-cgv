import os
import time

import pytest
from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC


BASE_URL = "http://127.0.0.1:5173"
EMAIL = "admin@gmail.com"
PASSWORD = "123456"

EVIDENCE_DIR = "evidence"
os.makedirs(EVIDENCE_DIR, exist_ok=True)


@pytest.fixture
def driver():
    browser = webdriver.Chrome()
    browser.maximize_window()

    yield browser

    time.sleep(1)
    browser.quit()


def chup_anh(driver, ten_file):
    driver.save_screenshot(
        os.path.join(EVIDENCE_DIR, ten_file)
    )


def mo_trang_dang_nhap(driver):
    driver.get(f"{BASE_URL}/internal/login")

    WebDriverWait(driver, 10).until(
        EC.visibility_of_element_located(
            (By.NAME, "email")
        )
    )

    assert "/internal/login" in driver.current_url


# ==========================================================
# TC01 - ĐĂNG NHẬP ĐÚNG THÔNG TIN
# ==========================================================

def test_TC01_dang_nhap_dung_thong_tin(driver):
    print("\n==============================")
    print("TC01 - ĐĂNG NHẬP ĐÚNG THÔNG TIN")
    print("==============================")

    print("Bước 1: Mở trang đăng nhập")
    mo_trang_dang_nhap(driver)
    chup_anh(driver, "TC01_B1_mo_trang.png")
    print("PASS Bước 1")

    print("Bước 2: Nhập email và mật khẩu đúng")

    email_input = driver.find_element(By.NAME, "email")
    password_input = driver.find_element(By.NAME, "matKhau")

    email_input.send_keys(EMAIL)
    password_input.send_keys(PASSWORD)

    assert email_input.get_attribute("value") == EMAIL
    assert password_input.get_attribute("value") == PASSWORD

    chup_anh(driver, "TC01_B2_nhap_du_lieu.png")
    print("PASS Bước 2")

    print("Bước 3: Nhấn Đăng nhập")

    driver.find_element(
        By.CSS_SELECTOR,
        "button[type='submit']"
    ).click()

    try:
        WebDriverWait(driver, 10).until(
            EC.url_contains("/dashboard")
        )
    except TimeoutException:
        errors = driver.find_elements(
            By.CLASS_NAME,
            "internal-error"
        )

        if errors:
            print("LỖI THỰC TẾ:", errors[0].text)

        chup_anh(driver, "TC01_FAIL.png")

        raise AssertionError(
            "Thông tin đúng nhưng không vào Dashboard."
        )

    assert "/dashboard" in driver.current_url

    chup_anh(
        driver,
        "TC01_B3_dang_nhap_thanh_cong.png"
    )

    print("PASS Bước 3: Đăng nhập thành công")


# ==========================================================
# TC02 - ĐĂNG NHẬP SAI MẬT KHẨU
# ==========================================================

def test_TC02_dang_nhap_sai_mat_khau(driver):
    print("\n==============================")
    print("TC02 - ĐĂNG NHẬP SAI MẬT KHẨU")
    print("==============================")

    print("Bước 1: Mở trang đăng nhập")
    mo_trang_dang_nhap(driver)
    chup_anh(driver, "TC02_B1_mo_trang.png")
    print("PASS Bước 1")

    print("Bước 2: Nhập email đúng, mật khẩu sai")

    driver.find_element(
        By.NAME,
        "email"
    ).send_keys(EMAIL)

    driver.find_element(
        By.NAME,
        "matKhau"
    ).send_keys("sai-mat-khau")

    chup_anh(
        driver,
        "TC02_B2_nhap_sai_mat_khau.png"
    )

    print("PASS Bước 2")

    print("Bước 3: Nhấn Đăng nhập")

    driver.find_element(
        By.CSS_SELECTOR,
        "button[type='submit']"
    ).click()

    try:
        error = WebDriverWait(driver, 10).until(
            EC.visibility_of_element_located(
                (By.CLASS_NAME, "internal-error")
            )
        )
    except TimeoutException:
        chup_anh(driver, "TC02_FAIL.png")

        raise AssertionError(
            "Sai mật khẩu nhưng không có thông báo lỗi."
        )

    print("Thông báo thực tế:", error.text)

    assert error.text.strip() != ""
    assert "/dashboard" not in driver.current_url

    chup_anh(
        driver,
        "TC02_B3_hien_thong_bao_loi.png"
    )

    print("PASS Bước 3: Hệ thống từ chối đăng nhập")


# ==========================================================
# TC03 - ĐỂ TRỐNG THÔNG TIN ĐĂNG NHẬP
# ==========================================================

def test_TC03_de_trong_thong_tin_dang_nhap(driver):
    print("\n==============================")
    print("TC03 - ĐỂ TRỐNG THÔNG TIN ĐĂNG NHẬP")
    print("==============================")

    print("Bước 1: Mở trang đăng nhập")
    mo_trang_dang_nhap(driver)
    chup_anh(driver, "TC03_B1_mo_trang.png")
    print("PASS Bước 1")

    print("Bước 2: Để trống email và mật khẩu")

    email_input = driver.find_element(
        By.NAME,
        "email"
    )

    password_input = driver.find_element(
        By.NAME,
        "matKhau"
    )

    assert email_input.get_attribute("value") == ""
    assert password_input.get_attribute("value") == ""

    chup_anh(
        driver,
        "TC03_B2_de_trong.png"
    )

    print("PASS Bước 2")

    print("Bước 3: Nhấn Đăng nhập")

    driver.find_element(
        By.CSS_SELECTOR,
        "button[type='submit']"
    ).click()

    time.sleep(0.5)

    email_valid = driver.execute_script(
        "return arguments[0].checkValidity();",
        email_input
    )

    password_valid = driver.execute_script(
        "return arguments[0].checkValidity();",
        password_input
    )

    assert email_valid is False
    assert password_valid is False
    assert "/internal/login" in driver.current_url

    chup_anh(
        driver,
        "TC03_B3_bat_buoc_nhap.png"
    )

    print(
        "PASS Bước 3: Hệ thống chặn vì chưa nhập dữ liệu"
    )
