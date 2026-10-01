import json
import os
import time
import urllib.error
import urllib.parse
import urllib.request
import zipfile
from pathlib import Path

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
DOWNLOAD_DIR = os.path.abspath("downloads")

os.makedirs(EVIDENCE_DIR, exist_ok=True)
os.makedirs(DOWNLOAD_DIR, exist_ok=True)


def cho(seconds=ACTION_DELAY):
    time.sleep(seconds)


def chup_anh(driver, ten_file):
    driver.save_screenshot(
        os.path.join(EVIDENCE_DIR, ten_file)
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
    options = webdriver.ChromeOptions()

    options.add_experimental_option(
        "prefs",
        {
            "download.default_directory": DOWNLOAD_DIR,
            "download.prompt_for_download": False,
            "download.directory_upgrade": True,
            "safebrowsing.enabled": True,
        },
    )

    browser = webdriver.Chrome(
        options=options
    )

    browser.maximize_window()

    yield browser

    browser.quit()


def mo_trang_thong_ke(
    driver,
    admin,
):
    dat_phien_admin(
        driver,
        admin,
    )

    driver.get(
        f"{BASE_URL}/dashboard/thong-ke"
    )
    cho()

    WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.XPATH,
                "//h1[normalize-space()='Báo cáo thống kê']",
            )
        )
    )


def nhap_ngay(
    driver,
    element,
    value,
):
    """
    Gán YYYY-MM-DD cho input type=date.
    Dùng JS để tránh Chrome/Windows nhập sai thứ tự ngày/tháng/năm.
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

        setter.call(
            input,
            value
        );

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

    cho(0.8)

    assert (
        element.get_attribute("value")
        == value
    )


def chon_khoang_ngay(
    driver,
    tu_ngay,
    den_ngay,
):
    date_inputs = driver.find_elements(
        By.CSS_SELECTOR,
        ".statistics-filter input[type='date']",
    )

    assert len(date_inputs) >= 2, (
        "Không tìm thấy đủ 2 ô Từ ngày / Đến ngày."
    )

    nhap_ngay(
        driver,
        date_inputs[0],
        tu_ngay,
    )

    nhap_ngay(
        driver,
        date_inputs[1],
        den_ngay,
    )


def chon_loai_thong_ke(
    driver,
    loai,
):
    labels = {
        "ve": "Doanh thu vé",
        "combo": "Bắp nước / Combo",
        "phim": "Theo phim",
        "khuyen-mai": "Khuyến mại",
    }

    label = labels[loai]

    button = driver.find_element(
        By.XPATH,
        f"//div[contains(@class,'statistics-tabs')]"
        f"//button[normalize-space()='{label}']",
    )

    if "active" not in (
        button.get_attribute("class")
        or ""
    ):
        cho()
        button.click()
        cho()


def bam_xem_thong_ke(
    driver,
):
    button = WebDriverWait(
        driver,
        10,
    ).until(
        EC.element_to_be_clickable(
            (
                By.XPATH,
                "//button[normalize-space()='Xem thống kê']",
            )
        )
    )

    cho()
    button.click()

    WebDriverWait(
        driver,
        20,
    ).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".statistics-summary",
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".statistics-error",
                )
            ) > 0
    )

    cho()


def tim_loai_co_du_lieu(
    token,
):
    """
    Tìm một loại thống kê có dữ liệu trong năm 2026.
    Kịch bản Word cho phép chọn doanh thu vé / combo / phim / khuyến mãi.
    """
    tu_ngay = "2026-01-01"
    den_ngay = "2026-12-31"

    endpoints = [
        (
            "ve",
            "/quan-ly/thong-ke/doanh-thu-ve",
        ),
        (
            "combo",
            "/quan-ly/thong-ke/doanh-thu-combo",
        ),
        (
            "phim",
            "/quan-ly/thong-ke/theo-phim",
        ),
        (
            "khuyen-mai",
            "/quan-ly/thong-ke/khuyen-mai",
        ),
    ]

    query = urllib.parse.urlencode(
        {
            "tuNgay": tu_ngay,
            "denNgay": den_ngay,
        }
    )

    for loai, endpoint in endpoints:
        status, data = api_request(
            "GET",
            f"{endpoint}?{query}",
            token=token,
        )

        if (
            status == 200
            and data.get("coDuLieu")
        ):
            return {
                "loai": loai,
                "tuNgay": tu_ngay,
                "denNgay": den_ngay,
                "apiData": data,
            }

    return None


def xoa_file_excel_cu():
    for file in Path(
        DOWNLOAD_DIR
    ).glob("thong-ke-*.xlsx"):
        try:
            file.unlink()
        except OSError:
            pass


def cho_file_excel_moi(
    timeout=20,
):
    end_time = time.time() + timeout

    while time.time() < end_time:
        files = list(
            Path(
                DOWNLOAD_DIR
            ).glob(
                "thong-ke-*.xlsx"
            )
        )

        partials = list(
            Path(
                DOWNLOAD_DIR
            ).glob(
                "*.crdownload"
            )
        )

        if (
            files
            and not partials
        ):
            newest = max(
                files,
                key=lambda f:
                    f.stat().st_mtime,
            )

            if (
                newest.exists()
                and newest.stat().st_size > 0
            ):
                return newest

        time.sleep(0.5)

    raise AssertionError(
        "Không thấy file Excel được tải xuống."
    )


# ==========================================================
# TC32 - THỐNG KÊ DOANH THU TRONG KHOẢNG CÓ DỮ LIỆU
# FR-54 đến FR-57
# ==========================================================
def test_TC32_thong_ke_khoang_co_du_lieu(
    driver,
    admin,
):
    print("\n========================================")
    print("TC32 - THỐNG KÊ KHOẢNG CÓ DỮ LIỆU")
    print("========================================")

    thong_tin = tim_loai_co_du_lieu(
        admin["token"]
    )

    if not thong_tin:
        pytest.skip(
            "CSDL hiện chưa có giao dịch thanh toán thành công "
            "trong năm 2026 để kiểm thử TC32."
        )

    # BƯỚC 1
    print("Bước 1: Mở Báo cáo thống kê")

    mo_trang_thong_ke(
        driver,
        admin,
    )

    print(
        "PASS Bước 1: Trang thống kê hiển thị"
    )

    # BƯỚC 2
    print(
        "Bước 2: Chọn khoảng thời gian có dữ liệu"
    )

    chon_khoang_ngay(
        driver,
        thong_tin["tuNgay"],
        thong_tin["denNgay"],
    )

    print(
        "PASS Bước 2: Hệ thống nhận khoảng thời gian"
    )

    # BƯỚC 3
    print(
        "Bước 3: Chọn loại thống kê"
    )

    chon_loai_thong_ke(
        driver,
        thong_tin["loai"],
    )

    print(
        f"PASS Bước 3: Chọn loại '{thong_tin['loai']}'"
    )

    # BƯỚC 4
    print("Bước 4: Xem kết quả")

    bam_xem_thong_ke(
        driver
    )

    errors = driver.find_elements(
        By.CSS_SELECTOR,
        ".statistics-error",
    )

    assert not [
        item
        for item in errors
        if item.is_displayed()
        and item.text.strip()
    ]

    summary = WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".statistics-summary",
            )
        )
    )

    chart = driver.find_element(
        By.CSS_SELECTOR,
        ".statistics-chart-box",
    )

    table = driver.find_element(
        By.CSS_SELECTOR,
        ".statistics-table",
    )

    assert summary.is_displayed()
    assert chart.is_displayed()
    assert table.is_displayed()

    chup_anh(
        driver,
        "TC32_thong_ke_co_du_lieu.png",
    )

    print(
        "PASS Bước 4: Số liệu, biểu đồ và bảng thống kê hiển thị"
    )

    cho(3)


# ==========================================================
# TC33 - THỐNG KÊ KHOẢNG THỜI GIAN KHÔNG CÓ DỮ LIỆU
# FR-54 đến FR-57
# ==========================================================
def test_TC33_thong_ke_khoang_khong_co_du_lieu(
    driver,
    admin,
):
    print("\n========================================")
    print("TC33 - THỐNG KÊ KHOẢNG KHÔNG CÓ DỮ LIỆU")
    print("========================================")

    # BƯỚC 1
    print("Bước 1: Mở Báo cáo thống kê")

    mo_trang_thong_ke(
        driver,
        admin,
    )

    print(
        "PASS Bước 1: Trang thống kê hiển thị"
    )

    # BƯỚC 2
    print(
        "Bước 2: Chọn khoảng không có giao dịch"
    )

    chon_loai_thong_ke(
        driver,
        "ve",
    )

    chon_khoang_ngay(
        driver,
        "2099-01-01",
        "2099-01-31",
    )

    bam_xem_thong_ke(
        driver
    )

    print(
        "PASS Bước 2: Hệ thống thực hiện truy vấn"
    )

    # BƯỚC 3
    print("Bước 3: Xem kết quả")

    empty = WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".statistics-empty",
            )
        )
    )

    expected = (
        "Không có dữ liệu trong khoảng thời gian này."
    )

    actual = " ".join(
        empty.text.split()
    )

    assert actual == expected

    chup_anh(
        driver,
        "TC33_thong_ke_khong_co_du_lieu.png",
    )

    print(
        "PASS Bước 3: Hiển thị đúng thông báo không có dữ liệu"
    )

    cho(3)


# ==========================================================
# TC34 - XUẤT BÁO CÁO THỐNG KÊ
# FR-58
# ==========================================================
def test_TC34_xuat_bao_cao_excel(
    driver,
    admin,
):
    print("\n========================================")
    print("TC34 - XUẤT BÁO CÁO THỐNG KÊ")
    print("========================================")

    thong_tin = tim_loai_co_du_lieu(
        admin["token"]
    )

    if not thong_tin:
        pytest.skip(
            "CSDL hiện chưa có dữ liệu thống kê để xuất báo cáo."
        )

    xoa_file_excel_cu()

    # BƯỚC 1
    print("Bước 1: Mở Báo cáo thống kê")

    mo_trang_thong_ke(
        driver,
        admin,
    )

    print(
        "PASS Bước 1: Trang thống kê hiển thị"
    )

    # BƯỚC 2
    print(
        "Bước 2: Chọn khoảng thời gian và xem thống kê"
    )

    chon_loai_thong_ke(
        driver,
        thong_tin["loai"],
    )

    chon_khoang_ngay(
        driver,
        thong_tin["tuNgay"],
        thong_tin["denNgay"],
    )

    bam_xem_thong_ke(
        driver
    )

    WebDriverWait(
        driver,
        10,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".statistics-export",
            )
        )
    )

    print(
        "PASS Bước 2: Kết quả thống kê được hiển thị"
    )

    # BƯỚC 3
    print("Bước 3: Chọn Xuất Excel")

    export_button = driver.find_element(
        By.XPATH,
        "//div[contains(@class,'statistics-export')]"
        "//button[normalize-space()='Xuất Excel']",
    )

    cho()
    export_button.click()

    excel_file = cho_file_excel_moi()

    print(
        f"PASS Bước 3: Tạo file {excel_file.name}"
    )

    # BƯỚC 4
    print("Bước 4: Kiểm tra file")

    assert excel_file.exists()
    assert excel_file.stat().st_size > 0

    assert zipfile.is_zipfile(
        excel_file
    ), (
        "File tải về không phải cấu trúc XLSX hợp lệ."
    )

    with zipfile.ZipFile(
        excel_file,
        "r",
    ) as archive:
        names = set(
            archive.namelist()
        )

        assert (
            "xl/workbook.xml"
            in names
        )

        assert (
            "xl/worksheets/sheet1.xml"
            in names
        )

    chup_anh(
        driver,
        "TC34_xuat_excel_thanh_cong.png",
    )

    print(
        "PASS Bước 4: File Excel tải thành công và có cấu trúc XLSX hợp lệ"
    )

    cho(3)
