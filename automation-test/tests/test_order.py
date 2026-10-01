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
    driver.get(f"{BASE_URL}/home")
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


def mo_trang_don_hang(
    driver,
    admin,
):
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

    WebDriverWait(
        driver,
        15,
    ).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".om-table-wrap",
                )
            ) > 0
            or len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".om-error",
                )
            ) > 0
    )


def lay_mot_don_hang(
    token,
):
    query = urllib.parse.urlencode(
        {
            "q": "",
            "trangThai": "",
            "page": 1,
        }
    )

    status, data = api_request(
        "GET",
        f"/quan-ly/don-hangs?{query}",
        token=token,
    )

    assert status == 200, (
        f"Không tải được danh sách đơn hàng. "
        f"HTTP {status}: {data.get('message', data)}"
    )

    orders = data.get(
        "data",
        [],
    )

    if not orders:
        pytest.skip(
            "Hệ thống hiện chưa có đơn hàng để kiểm thử tra cứu."
        )

    return orders[0]


# ==========================================================
# TC18 - TRA CỨU ĐƠN HÀNG TỒN TẠI
# FR-33
# ==========================================================
def test_TC18_tra_cuu_don_hang_ton_tai(
    driver,
    admin,
):
    print("\n========================================")
    print("TC18 - TRA CỨU ĐƠN HÀNG TỒN TẠI")
    print("========================================")

    order = lay_mot_don_hang(
        admin["token"]
    )

    ma_don = order["maDonHang"]

    # --------------------------------------------------
    # BƯỚC 1
    # --------------------------------------------------
    print("Bước 1: Mở trang tra cứu đơn")

    mo_trang_don_hang(
        driver,
        admin,
    )

    search = driver.find_element(
        By.CSS_SELECTOR,
        ".om-search input",
    )

    status_select = driver.find_element(
        By.CSS_SELECTOR,
        ".om-filters select",
    )

    assert search.is_displayed()
    assert status_select.is_displayed()

    print(
        "PASS Bước 1: Danh sách và ô tìm kiếm đơn hiển thị"
    )

    # --------------------------------------------------
    # BƯỚC 2
    # --------------------------------------------------
    print(
        f"Bước 2: Nhập mã đơn {ma_don}"
    )

    # Trang mặc định chỉ lọc Đã đặt.
    # Chuyển sang Tất cả để tra cứu được mọi trạng thái đơn.
    Select(
        status_select
    ).select_by_value("")

    cho()

    search.clear()
    search.send_keys(
        ma_don
    )

    cho()

    assert (
        search.get_attribute("value")
        == ma_don
    )

    print(
        "PASS Bước 2: Hệ thống nhận từ khóa tìm kiếm"
    )

    # --------------------------------------------------
    # BƯỚC 3
    # --------------------------------------------------
    print("Bước 3: Thực hiện tra cứu")

    # React tự tìm sau 200ms, không có nút Tìm kiếm.
    WebDriverWait(
        driver,
        15,
    ).until(
        lambda d:
            len(
                d.find_elements(
                    By.CSS_SELECTOR,
                    ".om-order-row",
                )
            ) == 1
            and ma_don
            in d.find_element(
                By.CSS_SELECTOR,
                ".om-order-row",
            ).text
    )

    row = driver.find_element(
        By.CSS_SELECTOR,
        ".om-order-row",
    )

    assert ma_don in row.text

    print(
        "PASS Bước 3: Tìm thấy đúng đơn hàng"
    )

    # --------------------------------------------------
    # BƯỚC 4 - kiểm tra thông tin liên quan
    # --------------------------------------------------
    print("Bước 4: Mở chi tiết đơn hàng")

    cho()

    driver.execute_script(
        "arguments[0].click();",
        row,
    )

    # Modal xuất hiện trước khi API chi tiết trả về.
    # Vì vậy phải chờ đúng nội dung chi tiết đơn hàng tải xong,
    # không chỉ chờ khung modal xuất hiện.
    detail = WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.CSS_SELECTOR,
                ".om-detail-modal",
            )
        )
    )

    title = WebDriverWait(
        driver,
        15,
    ).until(
        EC.visibility_of_element_located(
            (
                By.ID,
                "om-detail-title",
            )
        )
    )

    assert title.text.strip() == ma_don

    WebDriverWait(
        driver,
        10,
    ).until(
        lambda d:
            "Tổng thanh toán"
            in d.find_element(
                By.CSS_SELECTOR,
                ".om-detail-modal",
            ).text
    )

    detail = driver.find_element(
        By.CSS_SELECTOR,
        ".om-detail-modal",
    )

    assert (
        "Tổng thanh toán"
        in detail.text
    )

    chup_anh(
        driver,
        "TC18_tra_cuu_don_hang.png",
    )

    print(
        "PASS Bước 4: Chi tiết đúng mã đơn và thông tin liên quan hiển thị"
    )

    cho(3)
