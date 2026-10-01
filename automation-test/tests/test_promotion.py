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


def chup_anh(driver, ten_file):
    driver.save_screenshot(
        os.path.join(EVIDENCE_DIR, ten_file)
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


def mo_trang_khuyen_mai(driver, admin):
    dat_phien_admin(
        driver,
        admin,
    )

    driver.get(
        f"{BASE_URL}/dashboard/khuyen-mai"
    )
    cho()

    WebDriverWait(driver, 15).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Quản lý khuyến mại']",
            )
        )
    )

    WebDriverWait(driver, 15).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".cm-table-wrap table",
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".cm-empty",
                )
            ) > 0
    )


def mo_form_them(driver):
    button = WebDriverWait(
        driver,
        10,
    ).until(
        EC.element_to_be_clickable(
            (
                By.XPATH,
                "//button[contains(normalize-space(.),'Thêm khuyến mại')]",
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
                "dialog",
            )
        )
    )

    heading = dialog.find_element(
        By.ID,
        "pm-title",
    )

    assert (
        "Thêm khuyến mại"
        in heading.text
    )

    return dialog


def nhap_input(dialog, name, value):
    element = dialog.find_element(
        By.NAME,
        name,
    )

    element.clear()
    element.send_keys(str(value))
    cho(0.5)

    return element


def nhap_ngay(driver, dialog, name, value):
    """
    Gán giá trị YYYY-MM-DD cho input type=date bằng JavaScript.

    Không dùng send_keys() vì Chrome/Windows có thể nhập theo từng
    phần ngày-tháng-năm và biến 2026-10-02 thành năm 61002...
    """
    element = dialog.find_element(
        By.NAME,
        name,
    )

    driver.execute_script(
        """
        const input = arguments[0];
        const value = arguments[1];

        const setter = Object.getOwnPropertyDescriptor(
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

    cho(0.8)

    actual = element.get_attribute("value")

    assert actual == value, (
        f"Không nhập đúng ngày cho {name}. "
        f"Mong đợi {value}, thực tế {actual}"
    )

    return element


def nhap_form_khuyen_mai(
    driver,
    dialog,
    ma_km,
    ten_km,
    ngay_bat_dau,
    ngay_ket_thuc,
):
    nhap_input(
        dialog,
        "hinhAnh",
        "https://picsum.photos/600/300",
    )

    nhap_input(
        dialog,
        "maKM",
        ma_km,
    )

    nhap_input(
        dialog,
        "tenKM",
        ten_km,
    )

    Select(
        dialog.find_element(
            By.NAME,
            "hinhThuc",
        )
    ).select_by_value(
        "GIAM_PHAN_TRAM"
    )
    cho(0.5)

    nhap_input(
        dialog,
        "giaTri",
        "10",
    )

    nhap_input(
        dialog,
        "donToiThieu",
        "100000",
    )

    Select(
        dialog.find_element(
            By.NAME,
            "trangThai",
        )
    ).select_by_value(
        "HOAT_DONG"
    )
    cho(0.5)

    nhap_ngay(
        driver,
        dialog,
        "ngayBatDau",
        ngay_bat_dau,
    )

    nhap_ngay(
        driver,
        dialog,
        "ngayKetThuc",
        ngay_ket_thuc,
    )


def bam_luu(driver):
    button = WebDriverWait(
        driver,
        10,
    ).until(
        EC.element_to_be_clickable(
            (
                By.XPATH,
                "//dialog//button[normalize-space()='Lưu khuyến mại']",
            )
        )
    )

    cho()
    button.click()
    cho()


# ==========================================================
# TC29 - THÊM KHUYẾN MÃI HỢP LỆ
# FR-50
# ==========================================================
def test_TC29_them_khuyen_mai_hop_le(driver, admin):
    print("\n========================================")
    print("TC29 - THÊM KHUYẾN MÃI HỢP LỆ")
    print("========================================")

    ma_km = "KMAUTO" + uuid.uuid4().hex[:6].upper()
    ten_km = "Khuyến mãi Selenium " + uuid.uuid4().hex[:4]

    today = date.today()
    start_date = today.isoformat()
    end_date = (today + timedelta(days=30)).isoformat()

    created = False

    try:
        # BƯỚC 1
        print("Bước 1: Mở Quản lý khuyến mại")

        mo_trang_khuyen_mai(
            driver,
            admin,
        )

        print(
            "PASS Bước 1: Danh sách khuyến mại hiển thị"
        )

        # BƯỚC 2
        print("Bước 2: Chọn Thêm khuyến mại")

        dialog = mo_form_them(
            driver
        )

        assert dialog.is_displayed()

        print(
            "PASS Bước 2: Form thêm khuyến mại hiển thị"
        )

        # BƯỚC 3
        print("Bước 3: Nhập dữ liệu hợp lệ")

        nhap_form_khuyen_mai(
            driver,
            dialog,
            ma_km,
            ten_km,
            start_date,
            end_date,
        )

        assert (
            dialog.find_element(
                By.NAME,
                "maKM",
            ).get_attribute("value")
            == ma_km
        )

        print(
            "PASS Bước 3: Dữ liệu hợp lệ được nhập đầy đủ"
        )

        # BƯỚC 4
        print("Bước 4: Nhấn Lưu khuyến mại")

        bam_luu(driver)

        notice = WebDriverWait(
            driver,
            15,
        ).until(
            EC.visibility_of_element_located(
                (
                    By.CSS_SELECTOR,
                    ".cm-notice",
                )
            )
        )

        assert (
            "Đã thêm khuyến mại"
            in notice.text
        )

        created = True

        # Tìm lại mã vừa tạo để xác nhận có trong danh sách.
        search = driver.find_element(
            By.CSS_SELECTOR,
            "input[type='search']",
        )

        search.clear()
        search.send_keys(ma_km)
        cho()

        WebDriverWait(driver, 10).until(
            lambda d:
                ma_km
                in d.find_element(
                    By.CSS_SELECTOR,
                    ".cm-card",
                ).text
        )

        chup_anh(
            driver,
            "TC29_them_khuyen_mai_hop_le.png",
        )

        print(
            "PASS Bước 4: Khuyến mại được tạo và hiển thị trong danh sách"
        )

        cho(3)

    finally:
        if created:
            # Cleanup: hệ thống DELETE thực tế chuyển trạng thái
            # sang NGUNG_HOAT_DONG, không xóa lịch sử.
            api_request(
                "DELETE",
                f"/khuyen-mais/{ma_km}",
                token=admin["token"],
            )


# ==========================================================
# TC30 - KHÔNG CHO NGÀY KẾT THÚC TRƯỚC NGÀY BẮT ĐẦU
# FR-50
# ==========================================================
def test_TC30_khong_cho_ngay_ket_thuc_truoc_ngay_bat_dau(
    driver,
    admin,
):
    print("\n========================================")
    print("TC30 - KHUYẾN MÃI CÓ KHOẢNG NGÀY KHÔNG HỢP LỆ")
    print("========================================")

    ma_km = "KMFAIL" + uuid.uuid4().hex[:6].upper()
    ten_km = "Khuyến mãi ngày sai"

    today = date.today()
    start_date = (today + timedelta(days=10)).isoformat()
    end_date = (today + timedelta(days=5)).isoformat()

    # BƯỚC 1
    print("Bước 1: Mở form khuyến mại")

    mo_trang_khuyen_mai(
        driver,
        admin,
    )

    dialog = mo_form_them(
        driver
    )

    assert dialog.is_displayed()

    print(
        "PASS Bước 1: Form thêm khuyến mại hiển thị"
    )

    # BƯỚC 2
    print("Bước 2: Nhập ngày kết thúc trước ngày bắt đầu")

    nhap_form_khuyen_mai(
        driver,
        dialog,
        ma_km,
        ten_km,
        start_date,
        end_date,
    )

    assert (
        dialog.find_element(
            By.NAME,
            "ngayKetThuc",
        ).get_attribute("value")
        == end_date
    )

    print(
        "PASS Bước 2: Dữ liệu khoảng ngày sai đã được nhập"
    )

    # BƯỚC 3
    print("Bước 3: Nhấn Lưu khuyến mại")

    # Ô Ngày kết thúc có thuộc tính min = Ngày bắt đầu.
    # Khi ngày kết thúc < ngày bắt đầu, trình duyệt chặn submit
    # ngay ở tầng HTML5 nên React không chạy onSubmit và không tạo .cm-error.
    end_input = dialog.find_element(
        By.NAME,
        "ngayKetThuc",
    )

    start_input = dialog.find_element(
        By.NAME,
        "ngayBatDau",
    )

    assert (
        end_input.get_attribute("min")
        == start_input.get_attribute("value")
    )

    bam_luu(driver)

    cho(1)

    # Kiểm tra native validation của trình duyệt.
    is_valid = driver.execute_script(
        "return arguments[0].checkValidity();",
        end_input,
    )

    validation_message = driver.execute_script(
        "return arguments[0].validationMessage;",
        end_input,
    )

    assert is_valid is False

    assert validation_message.strip() != ""

    # Form vẫn phải mở vì dữ liệu không được gửi/lưu.
    assert dialog.is_displayed()

    # Kiểm tra backend không có mã khuyến mãi này.
    status_check, response_check = api_request(
        "GET",
        (
            "/quan-ly/khuyen-mais?"
            + urllib.parse.urlencode(
                {
                    "q": ma_km,
                    "page": 1,
                }
            )
        ),
        token=admin["token"],
    )

    assert status_check == 200

    assert not any(
        item.get("maKM") == ma_km
        for item in response_check.get(
            "data",
            [],
        )
    )

    chup_anh(
        driver,
        "TC30_ngay_khuyen_mai_khong_hop_le.png",
    )

    print(
        "PASS Bước 3: Trình duyệt chặn lưu do ngày kết thúc "
        "trước ngày bắt đầu"
    )

    cho(3)


# ==========================================================
# TC31 - TRA CỨU KHUYẾN MÃI
# FR-52
# ==========================================================
def test_TC31_tra_cuu_khuyen_mai(driver, admin):
    print("\n========================================")
    print("TC31 - TRA CỨU KHUYẾN MÃI")
    print("========================================")

    # Lấy một khuyến mãi thật trong hệ thống để không phụ thuộc mã cố định.
    status, promotions = api_request(
        "GET",
        "/quan-ly/khuyen-mais?page=1",
        token=admin["token"],
    )

    assert status == 200, (
        f"Không lấy được dữ liệu khuyến mại. HTTP {status}"
    )

    data = promotions.get("data", [])

    if not data:
        pytest.skip(
            "Hệ thống chưa có khuyến mại để kiểm thử tra cứu."
        )

    promotion = data[0]
    keyword = promotion["maKM"]
    expected_name = promotion["tenKM"]

    # BƯỚC 1
    print("Bước 1: Mở Quản lý khuyến mại")

    mo_trang_khuyen_mai(
        driver,
        admin,
    )

    assert (
        driver.find_element(
            By.CSS_SELECTOR,
            ".cm-card",
        ).is_displayed()
    )

    print(
        "PASS Bước 1: Danh sách khuyến mại hiển thị"
    )

    # BƯỚC 2
    print(
        f"Bước 2: Nhập từ khóa '{keyword}'"
    )

    search = driver.find_element(
        By.CSS_SELECTOR,
        "input[type='search']",
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
    print("Bước 3: Thực hiện tra cứu")

    WebDriverWait(driver, 10).until(
        lambda d:
            keyword
            in d.find_element(
                By.CSS_SELECTOR,
                ".cm-card",
            ).text
    )

    result_text = driver.find_element(
        By.CSS_SELECTOR,
        ".cm-card",
    ).text

    assert keyword in result_text
    assert expected_name in result_text

    chup_anh(
        driver,
        "TC31_tra_cuu_khuyen_mai.png",
    )

    print(
        "PASS Bước 3: Kết quả tra cứu khuyến mại chính xác"
    )

    cho(3)


# ==========================================================
# TC38 - XÓA / NGỪNG ÁP DỤNG KHUYẾN MÃI
# FR-53
# ==========================================================
def test_TC38_xoa_khuyen_mai(driver, admin):
    print("\n========================================")
    print("TC38 - XÓA / NGỪNG ÁP DỤNG KHUYẾN MÃI")
    print("========================================")

    ma_km = "KMDEL" + uuid.uuid4().hex[:6].upper()
    ten_km = "Khuyến mãi xóa Selenium " + uuid.uuid4().hex[:4]

    today = date.today()
    start_date = today.isoformat()
    end_date = (today + timedelta(days=30)).isoformat()

    # Tạo dữ liệu riêng cho TC38 qua API để không ảnh hưởng khuyến mãi thật.
    status, response = api_request(
        "POST",
        "/khuyen-mais",
        {
            "maKM": ma_km,
            "tenKM": ten_km,
            "hinhAnh": "https://picsum.photos/600/300",
            "hinhThuc": "GIAM_PHAN_TRAM",
            "giaTri": 10,
            "donToiThieu": 100000,
            "ngayBatDau": start_date,
            "ngayKetThuc": end_date,
            "trangThai": "HOAT_DONG",
        },
        token=admin["token"],
    )

    assert status == 201, (
        f"Không tạo được khuyến mãi test TC38. "
        f"HTTP {status}: {response}"
    )

    # --------------------------------------------------
    # BƯỚC 1
    # --------------------------------------------------
    print("Bước 1: Mở Quản lý khuyến mại")

    mo_trang_khuyen_mai(
        driver,
        admin,
    )

    search = driver.find_element(
        By.CSS_SELECTOR,
        "input[type='search']",
    )

    search.clear()
    search.send_keys(ma_km)
    cho()

    row = WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                f"//tr[.//small[contains(normalize-space(.),'{ma_km}')]]",
            )
        )
    )

    assert ma_km in row.text

    print(
        "PASS Bước 1: Khuyến mãi test hiển thị trong danh sách"
    )

    # --------------------------------------------------
    # BƯỚC 2
    # --------------------------------------------------
    print("Bước 2: Chọn Xóa khuyến mại")

    delete_button = row.find_element(
        By.CSS_SELECTOR,
        "button.cm-danger",
    )

    assert delete_button.is_enabled()

    cho()
    delete_button.click()
    cho()

    dialog = WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                "dialog.cm-dialog",
            )
        )
    )

    heading = dialog.find_element(
        By.ID,
        "pm-title",
    )

    assert "Xóa khuyến mại?" in heading.text
    assert ma_km in dialog.text

    print(
        "PASS Bước 2: Hệ thống yêu cầu xác nhận xóa"
    )

    # --------------------------------------------------
    # BƯỚC 3
    # --------------------------------------------------
    print("Bước 3: Xác nhận xóa")

    confirm_button = dialog.find_element(
        By.CSS_SELECTOR,
        "button.cm-delete-button",
    )

    assert (
        confirm_button.text.strip()
        == "Xác nhận xóa"
    )

    cho()
    confirm_button.click()
    cho()

    notice = WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".cm-notice",
            )
        )
    )

    assert (
        "Đã ngừng áp dụng khuyến mại"
        in notice.text
    )

    # Kiểm tra lại đúng mã trong danh sách.
    search = driver.find_element(
        By.CSS_SELECTOR,
        "input[type='search']",
    )

    search.clear()
    search.send_keys(ma_km)
    cho()

    row_after = WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                f"//tr[.//small[contains(normalize-space(.),'{ma_km}')]]",
            )
        )
    )

    assert (
        "Ngừng hoạt động"
        in row_after.text
    )

    delete_after = row_after.find_element(
        By.CSS_SELECTOR,
        "button.cm-danger",
    )

    assert not delete_after.is_enabled()

    # Xác nhận trực tiếp backend.
    status_check, response_check = api_request(
        "GET",
        (
            "/quan-ly/khuyen-mais?"
            + urllib.parse.urlencode(
                {
                    "q": ma_km,
                    "trangThai": "NGUNG_HOAT_DONG",
                    "page": 1,
                }
            )
        ),
        token=admin["token"],
    )

    assert status_check == 200

    rows = response_check.get(
        "data",
        [],
    )

    assert any(
        item.get("maKM") == ma_km
        and item.get("trangThai") == "NGUNG_HOAT_DONG"
        for item in rows
    )

    chup_anh(
        driver,
        "TC38_xoa_khuyen_mai.png",
    )

    print(
        "PASS Bước 3: Khuyến mãi chuyển sang Ngừng hoạt động"
    )

    cho(3)
