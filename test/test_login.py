"""Visible Selenium scenarios for customer sign-in."""

import os
import time
import unittest

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait


BASE_URL = os.getenv("CGV_BASE_URL", "http://localhost:5173")
CUSTOMER_IDENTIFIER = os.getenv("CGV_TEST_IDENTIFIER", "")
CUSTOMER_PASSWORD = os.getenv("CGV_TEST_PASSWORD", "")
SUBMIT = (By.CSS_SELECTOR, "form.login-form button[type='submit']")


def replace_input_value(element, value):
    """Clear a controlled React input and enter a new value."""
    element.click()
    element.send_keys(Keys.CONTROL, "a")
    element.send_keys(Keys.BACKSPACE)
    if element.get_attribute("value"):
        element.clear()
    if element.get_attribute("value"):
        raise AssertionError("Không xóa được nội dung cũ trong ô nhập.")
    element.send_keys(value)
    if element.get_attribute("value") != value:
        raise AssertionError("Dữ liệu nhập vào không khớp giá trị kiểm thử.")


class CustomerLoginTest(unittest.TestCase):
    def setUp(self):
        options = webdriver.ChromeOptions()
        options.add_argument("--window-size=1365,900")
        self.driver = webdriver.Chrome(options=options)
        self.wait = WebDriverWait(self.driver, 10)
        self.driver.get(f"{BASE_URL}/login")
        self.results = []

    def record(self, name, passed, detail):
        status = "SKIP" if passed is None else "PASS" if passed else "FAIL"
        self.results.append({"name": name, "status": status, "detail": detail})
        self.driver.execute_script(
            """
            (function (result) {
              let panel = document.getElementById('selenium-test-results');
              if (!panel) {
                panel = document.createElement('aside');
                panel.id = 'selenium-test-results';
                Object.assign(panel.style, {
                  position: 'fixed', right: '16px', top: '16px', zIndex: '999999',
                  width: '360px', maxHeight: '70vh', overflow: 'auto',
                  padding: '16px', background: '#fff', color: '#222',
                  border: '2px solid #555', borderRadius: '8px',
                  boxShadow: '0 4px 18px #0005', font: '14px Arial, sans-serif'
                });
                document.body.appendChild(panel);
              }
              const row = document.createElement('div');
              row.style.cssText = 'padding:8px 0;border-bottom:1px solid #ddd';
              const color = result.status === 'PASS' ? '#16803c' :
                result.status === 'SKIP' ? '#8a6d00' : '#c62828';
              row.innerHTML = '<b style="color:' + color + '">' + result.status +
                '</b> — ' + result.name + '<br><small>' + result.detail + '</small>';
              panel.appendChild(row);
            })(arguments[0]);
            """,
            {"name": name, "status": status, "detail": detail},
        )
        time.sleep(4)

    def test_customer_login_scenarios(self):
        failures = []

        # Case 1: login page and required fields are visible.
        try:
            identifier = self.wait.until(
                EC.visibility_of_element_located((By.NAME, "identifier"))
            )
            password = self.driver.find_element(By.NAME, "matKhau")
            submit = self.driver.find_element(*SUBMIT)
            valid_form = all(
                [
                    identifier.is_displayed(),
                    password.is_displayed(),
                    submit.is_displayed(),
                    identifier.get_attribute("required") is not None,
                    password.get_attribute("required") is not None,
                ]
            )
            self.record(
                "Màn hình và trường bắt buộc",
                valid_form,
                "Form đăng nhập hiển thị" if valid_form else "Thiếu trường hoặc nút đăng nhập",
            )
            if not valid_form:
                failures.append("Form đăng nhập không hiển thị đầy đủ")
        except Exception as error:
            self.record("Màn hình và trường bắt buộc", False, str(error))
            failures.append("Không tải được form đăng nhập")

        # Case 2: submitting an empty form should be blocked by required fields.
        try:
            identifier = self.driver.find_element(By.NAME, "identifier")
            password = self.driver.find_element(By.NAME, "matKhau")
            replace_input_value(identifier, "")
            replace_input_value(password, "")
            self.driver.find_element(*SUBMIT).click()
            empty_form_blocked = self.driver.execute_script(
                "return !arguments[0].checkValidity() && !arguments[1].checkValidity()",
                identifier,
                password,
            )
            self.record(
                "Để trống trường thông tin",
                empty_form_blocked and self.driver.current_url.endswith("/login"),
                "Form bị trình duyệt chặn vì thiếu thông tin"
                if empty_form_blocked
                else "Form chưa chặn khi để trống thông tin",
            )
            if not empty_form_blocked:
                failures.append("Form không chặn gửi khi bỏ trống trường bắt buộc")
        except Exception as error:
            self.record("Để trống trường thông tin", False, str(error))
            failures.append("Không xác nhận được kiểm tra trường để trống")

        # Case 3: invalid credentials should show an error and stay on login.
        try:
            identifier = self.driver.find_element(By.NAME, "identifier")
            password = self.driver.find_element(By.NAME, "matKhau")
            replace_input_value(identifier, "selenium-invalid@example.com")
            replace_input_value(password, "incorrect-password-for-test")
            self.driver.find_element(*SUBMIT).click()
            error = self.wait.until(
                EC.visibility_of_element_located((By.CSS_SELECTOR, ".login-error"))
            )
            invalid_rejected = bool(error.text.strip()) and self.driver.current_url.endswith(
                "/login"
            )
            self.record(
                "Đăng nhập sai thông tin",
                invalid_rejected,
                error.text.strip() or "Không thấy thông báo lỗi",
            )
            if not invalid_rejected:
                failures.append("Thông tin sai không bị từ chối như mong đợi")
        except Exception as error:
            self.record("Đăng nhập sai thông tin", False, str(error))
            failures.append("Không xác nhận được đăng nhập sai")

        # Case 3: valid account values are entered into the real login form.
        if CUSTOMER_IDENTIFIER and CUSTOMER_PASSWORD:
            try:
                identifier = self.wait.until(
                    EC.visibility_of_element_located((By.NAME, "identifier"))
                )
                password = self.driver.find_element(By.NAME, "matKhau")
                replace_input_value(identifier, CUSTOMER_IDENTIFIER)
                replace_input_value(password, CUSTOMER_PASSWORD)
                self.driver.find_element(*SUBMIT).click()
                self.wait.until(
                    lambda driver: "/home" in driver.current_url
                    or driver.find_elements(By.CSS_SELECTOR, ".login-error")
                    and driver.find_element(By.CSS_SELECTOR, ".login-error").is_displayed()
                )
                logged_in = self.driver.execute_script(
                    "return Boolean(localStorage.getItem('token'))"
                )
                passed = self.driver.current_url.endswith("/home") and logged_in
                login_errors = self.driver.find_elements(
                    By.CSS_SELECTOR, ".login-error"
                )
                detail = (
                    "Đã chuyển tới trang chủ"
                    if passed
                    else login_errors[0].text.strip()
                    if login_errors and login_errors[0].is_displayed()
                    else f"Không chuyển tới trang chủ (URL: {self.driver.current_url}; token: {'có' if logged_in else 'không có'})"
                )
                self.record(
                    "Đăng nhập đúng thông tin",
                    passed,
                    detail,
                )
                if not passed:
                    failures.append(f"Đăng nhập đúng không thành công: {detail}")
            except Exception as error:
                login_errors = self.driver.find_elements(
                    By.CSS_SELECTOR, ".login-error"
                )
                detail = (
                    login_errors[0].text.strip()
                    if login_errors and login_errors[0].is_displayed()
                    else f"{type(error).__name__}: không nhận được phản hồi đăng nhập trong 10 giây"
                )
                self.record("Đăng nhập đúng thông tin", False, detail)
                failures.append(f"Không xác nhận được đăng nhập đúng: {detail}")
        else:
            self.record(
                "Đăng nhập đúng thông tin",
                None,
                "Bỏ qua: chưa đặt CGV_TEST_IDENTIFIER và CGV_TEST_PASSWORD",
            )

        # Keep Chrome open so the user can inspect the page and result panel.
        self.driver.execute_script(
            """const p = document.getElementById('selenium-test-results');
               if (p) { const note = document.createElement('p');
                 note.textContent = 'Đã chạy xong. Nhấn Enter trong cửa sổ terminal để đóng trình duyệt.';
                 p.prepend(note); }"""
        )
        input("Đã hiện kết quả trên trình duyệt. Nhấn Enter để đóng Chrome...")

        if failures:
            self.fail("; ".join(failures))

    def tearDown(self):
        if hasattr(self, "driver"):
            self.driver.quit()


if __name__ == "__main__":
    unittest.main(verbosity=2)
