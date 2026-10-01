import os
import time
import uuid

import pytest
from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait


BASE_URL = "http://127.0.0.1:5173"
EVIDENCE_DIR = "evidence"

os.makedirs(EVIDENCE_DIR, exist_ok=True)


# ==========================================================
# FIXTURE CHROME
# ==========================================================

@pytest.fixture
def driver():
    browser = webdriver.Chrome()
    browser.maximize_window()

    yield browser

    time.sleep(1)
    browser.quit()


# ==========================================================
# HÀM DÙNG CHUNG
# ==========================================================

def chup_anh(driver, ten_file):
    duong_dan = os.path.join(EVIDENCE_DIR, ten_file)
    driver.save_screenshot(duong_dan)


def mo_trang_dang_ky(driver):
    driver.get(f"{BASE_URL}/register")

    WebDriverWait(driver, 10).until(
        lambda d: len(d.find_elements(By.NAME, "hoTen")) > 0
    )

    assert "/register" in driver.current_url


def nhap_form_dang_ky(
    driver,
    ho_ten,
    so_dien_thoai,
    email,
    ten_dang_nhap,
    mat_khau="123456",
):
    driver.find_element(By.NAME, "hoTen").clear()
    driver.find_element(By.NAME, "hoTen").send_keys(ho_ten)

    driver.find_element(By.NAME, "soDienThoai").clear()
    driver.find_element(By.NAME, "soDienThoai").send_keys(so_dien_thoai)

    driver.find_element(By.NAME, "email").clear()
    driver.find_element(By.NAME, "email").send_keys(email)

    # ngaySinh và gioiTinh là trường không bắt buộc,
    # nên bỏ trống để test đăng ký tập trung vào dữ liệu bắt buộc.

    driver.find_element(By.NAME, "tenDangNhap").clear()
    driver.find_element(By.NAME, "tenDangNhap").send_keys(ten_dang_nhap)

    driver.find_element(By.NAME, "matKhau").clear()
    driver.find_element(By.NAME, "matKhau").send_keys(mat_khau)

    driver.find_element(By.NAME, "matKhau_confirmation").clear()
    driver.find_element(By.NAME, "matKhau_confirmation").send_keys(mat_khau)


def kiem_tra_form_html_hop_le(driver):
    """
    Kiểm tra các ràng buộc HTML như:
    required, type=email, pattern, minlength...
    """
    required_elements = driver.find_elements(
        By.CSS_SELECTOR,
        "form input[required], form select[required]"
    )

    loi = []

    for element in required_elements:
        hop_le = driver.execute_script(
            "return arguments[0].checkValidity();",
            element,
        )

        if not hop_le:
            ten = element.get_attribute("name") or "(không có name)"
            thong_bao = driver.execute_script(
                "return arguments[0].validationMessage;",
                element,
            )
            loi.append(f"{ten}: {thong_bao}")

    assert not loi, "Form không hợp lệ:\n" + "\n".join(loi)


def cho_ket_qua_dang_ky(driver, timeout=15):
    """
    Trả về tuple:
      ("ERROR", nội_dung)
      ("SUCCESS", nội_dung)
      ("LOGIN_FORM", email_được_điền_sẵn)

    Frontend hiện tại có thể:
    - hiện .login-success trong thời gian ngắn; hoặc
    - sau khoảng 1 giây chuyển sang tab đăng nhập.
    """

    def lay_ket_qua(d):
        # 1. Ưu tiên bắt lỗi
        for error in d.find_elements(By.CLASS_NAME, "login-error"):
            if error.is_displayed() and error.text.strip():
                return ("ERROR", error.text.strip())

        # 2. Bắt thông báo thành công
        for success in d.find_elements(By.CLASS_NAME, "login-success"):
            if success.is_displayed() and success.text.strip():
                return ("SUCCESS", success.text.strip())

        # 3. Sau đăng ký thành công frontend chuyển sang form đăng nhập
        for identifier in d.find_elements(By.NAME, "identifier"):
            if identifier.is_displayed():
                return (
                    "LOGIN_FORM",
                    identifier.get_attribute("value") or "",
                )

        return False

    return WebDriverWait(driver, timeout).until(lay_ket_qua)


def tao_email_moi():
    unique = uuid.uuid4().hex[:10]
    return f"cgvtest{unique}@gmail.com"


def tao_so_dien_thoai_moi(dau_so="09"):
    """
    Sinh đúng 10 chữ số:
    2 số đầu + 8 chữ số ngẫu nhiên.
    """
    phan_sau = f"{uuid.uuid4().int % 100_000_000:08d}"
    return dau_so + phan_sau


# ==========================================================
# TC04 - ĐĂNG KÝ TÀI KHOẢN HỢP LỆ
# FR-01
# ==========================================================

def test_TC04_dang_ky_tai_khoan_hop_le(driver):
    print("\n====================================")
    print("TC04 - ĐĂNG KÝ TÀI KHOẢN HỢP LỆ")
    print("====================================")

    email = tao_email_moi()
    so_dien_thoai = tao_so_dien_thoai_moi("09")
    ten_dang_nhap = "cgv" + uuid.uuid4().hex[:8]

    # ------------------------------------------------------
    # BƯỚC 1
    # ------------------------------------------------------
    print("Bước 1: Mở trang đăng ký")

    mo_trang_dang_ky(driver)

    chup_anh(
        driver,
        "TC04_B1_mo_trang_dang_ky.png",
    )

    print(
        "PASS Bước 1: Trang đăng ký hiển thị thành công"
    )

    time.sleep(0.5)

    # ------------------------------------------------------
    # BƯỚC 2
    # ------------------------------------------------------
    print(
        "Bước 2: Nhập thông tin khách hàng hợp lệ"
    )

    nhap_form_dang_ky(
        driver=driver,
        ho_ten="Nguyễn Văn Test",
        so_dien_thoai=so_dien_thoai,
        email=email,
        ten_dang_nhap=ten_dang_nhap,
        mat_khau="123456",
    )

    # Kiểm tra dữ liệu đã được nhập thật
    assert (
        driver.find_element(By.NAME, "hoTen")
        .get_attribute("value")
        == "Nguyễn Văn Test"
    )

    assert (
        driver.find_element(By.NAME, "soDienThoai")
        .get_attribute("value")
        == so_dien_thoai
    )

    assert (
        driver.find_element(By.NAME, "email")
        .get_attribute("value")
        == email
    )

    assert (
        driver.find_element(By.NAME, "matKhau")
        .get_attribute("value")
        == "123456"
    )

    assert (
        driver.find_element(By.NAME, "matKhau_confirmation")
        .get_attribute("value")
        == "123456"
    )

    kiem_tra_form_html_hop_le(driver)

    chup_anh(
        driver,
        "TC04_B2_nhap_du_lieu_hop_le.png",
    )

    print(
        "PASS Bước 2: Dữ liệu hợp lệ được chấp nhận"
    )

    time.sleep(0.5)

    # ------------------------------------------------------
    # BƯỚC 3
    # ------------------------------------------------------
    print("Bước 3: Nhấn nút Đăng ký")

    driver.find_element(
        By.CSS_SELECTOR,
        "button[type='submit']",
    ).click()

    try:
        loai, noi_dung = cho_ket_qua_dang_ky(
            driver,
            timeout=15,
        )

    except TimeoutException:
        chup_anh(
            driver,
            "TC04_TIMEOUT.png",
        )

        raise AssertionError(
            "Sau khi nhấn Đăng ký, hệ thống không trả về "
            "thông báo thành công, form đăng nhập hoặc thông báo lỗi."
        )

    print(
        f"Kết quả hệ thống: {loai} - {noi_dung}"
    )

    if loai == "ERROR":
        chup_anh(
            driver,
            "TC04_DANG_KY_FAIL.png",
        )

        raise AssertionError(
            "Đăng ký bằng dữ liệu hợp lệ nhưng hệ thống báo lỗi: "
            + noi_dung
        )

    if loai == "SUCCESS":
        assert "thành công" in noi_dung.lower()

    elif loai == "LOGIN_FORM":
        # Sau khi tạo thành công, frontend tự điền email vừa đăng ký
        # vào form đăng nhập.
        assert noi_dung.lower() == email.lower()

    else:
        raise AssertionError(
            f"Kết quả đăng ký không xác định: {loai} - {noi_dung}"
        )

    chup_anh(
        driver,
        "TC04_B3_dang_ky_thanh_cong.png",
    )

    print(
        "PASS Bước 3: Tài khoản khách hàng được tạo thành công"
    )


# ==========================================================
# TC05 - ĐĂNG KÝ EMAIL ĐÃ TỒN TẠI
# FR-01
# ==========================================================

def test_TC05_dang_ky_email_da_ton_tai(driver):
    print("\n======================================")
    print("TC05 - ĐĂNG KÝ EMAIL ĐÃ TỒN TẠI")
    print("======================================")

    # Email này tồn tại trong dữ liệu mẫu của project.
    email_da_ton_tai = "nguyenan@gmail.com"

    # Số điện thoại và tên đăng nhập phải mới
    # để testcase chỉ kiểm tra lỗi trùng email.
    so_dien_thoai = tao_so_dien_thoai_moi("08")
    ten_dang_nhap = "duplicate" + uuid.uuid4().hex[:8]

    # ------------------------------------------------------
    # BƯỚC 1
    # ------------------------------------------------------
    print("Bước 1: Mở trang đăng ký")

    mo_trang_dang_ky(driver)

    chup_anh(
        driver,
        "TC05_B1_mo_trang_dang_ky.png",
    )

    print(
        "PASS Bước 1: Trang đăng ký hiển thị thành công"
    )

    time.sleep(0.5)

    # ------------------------------------------------------
    # BƯỚC 2
    # ------------------------------------------------------
    print(
        "Bước 2: Nhập email đã tồn tại trong hệ thống"
    )

    nhap_form_dang_ky(
        driver=driver,
        ho_ten="Khách Hàng Test",
        so_dien_thoai=so_dien_thoai,
        email=email_da_ton_tai,
        ten_dang_nhap=ten_dang_nhap,
        mat_khau="123456",
    )

    assert (
        driver.find_element(By.NAME, "email")
        .get_attribute("value")
        == email_da_ton_tai
    )

    kiem_tra_form_html_hop_le(driver)

    chup_anh(
        driver,
        "TC05_B2_nhap_email_trung.png",
    )

    print(
        "PASS Bước 2: Dữ liệu được nhập đầy đủ"
    )

    time.sleep(0.5)

    # ------------------------------------------------------
    # BƯỚC 3
    # ------------------------------------------------------
    print("Bước 3: Nhấn nút Đăng ký")

    driver.find_element(
        By.CSS_SELECTOR,
        "button[type='submit']",
    ).click()

    try:
        loai, noi_dung = cho_ket_qua_dang_ky(
            driver,
            timeout=15,
        )
    except TimeoutException:
        chup_anh(
            driver,
            "TC05_TIMEOUT.png",
        )

        raise AssertionError(
            "Hệ thống không trả về thông báo khi đăng ký "
            "bằng email đã tồn tại."
        )

    print(
        f"Kết quả hệ thống: {loai} - {noi_dung}"
    )

    # Kịch bản mong muốn: phải có lỗi
    assert loai == "ERROR", (
        "Email đã tồn tại nhưng hệ thống vẫn cho đăng ký "
        "hoặc không hiển thị lỗi."
    )

    noi_dung_lower = noi_dung.lower()

    # Hệ thống hiện có thể trả tiếng Việt hoặc validation mặc định tiếng Anh
    assert (
        "email đã được sử dụng" in noi_dung_lower
        or "email đã tồn tại" in noi_dung_lower
        or "email has already been taken" in noi_dung_lower
    ), (
        "Thông báo lỗi không đúng nội dung mong đợi. "
        f"Thông báo thực tế: {noi_dung}"
    )

    # Không được chuyển sang form đăng nhập
    assert "/register" in driver.current_url

    chup_anh(
        driver,
        "TC05_B3_canh_bao_email_trung.png",
    )

    print(
        "PASS Bước 3: Không tạo tài khoản mới; "
        "hệ thống phát hiện email đã tồn tại"
    )
