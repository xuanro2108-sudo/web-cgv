"""Visible Selenium scenarios for customer registration."""

import os
import time
import unittest
from datetime import date, timedelta

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import Select, WebDriverWait


BASE_URL = os.getenv("CGV_BASE_URL", "http://localhost:5173")
EXISTING_EMAIL = os.getenv("CGV_EXISTING_EMAIL", "")
SUBMIT = (By.CSS_SELECTOR, "form.login-form button[type='submit']")


def replace_input_value(driver, element, value):
    field_name = element.get_attribute("name") or "không rõ"
    element.click()
    element.clear()
    element.send_keys(value)
    try:
        WebDriverWait(driver, 3).until(
            lambda _driver: element.get_property("value") == value
        )
    except Exception as error:
        raise AssertionError(
            f"Không nhập đúng giá trị cho trường {field_name}."
        ) from error


def set_date_input_value(driver, element, value):
    """Set an HTML date input using its ISO value and notify React."""
    driver.execute_script(
        """
        const input = arguments[0];
        const value = arguments[1];
        const setter = Object.getOwnPropertyDescriptor(
          HTMLInputElement.prototype, 'value'
        ).set;
        setter.call(input, value);
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        """,
        element,
        value,
    )
    if element.get_property("value") != value:
        raise AssertionError("Không nhập được ngày sinh theo định dạng YYYY-MM-DD.")


class CustomerRegistrationTest(unittest.TestCase):
    def setUp(self):
        options = webdriver.ChromeOptions()
        options.add_argument("--window-size=1365,900")
        self.driver = webdriver.Chrome(options=options)
        self.wait = WebDriverWait(self.driver, 10)
        self.driver.get(f"{BASE_URL}/register")

    def record(self, name, passed, detail):
        status = "SKIP" if passed is None else "PASS" if passed else "FAIL"
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
            {
                "name": name,
                "status": status,
                "detail": detail,
            },
        )
        time.sleep(4)

    def open_register_tab(self):
        tabs = self.driver.find_elements(By.CSS_SELECTOR, ".auth-tabs button")
        if "active" not in (tabs[1].get_attribute("class") or "").split():
            self.wait.until(
                EC.element_to_be_clickable((By.CSS_SELECTOR, ".auth-tabs button"))
            )
            tabs = self.driver.find_elements(By.CSS_SELECTOR, ".auth-tabs button")
            tabs[1].click()
        self.wait.until(EC.visibility_of_element_located((By.NAME, "hoTen")))

    def fill_valid_form(self, email, phone):
        values = {
            "hoTen": "Selenium Test",
            "soDienThoai": phone,
            "email": email,
            "tenDangNhap": email,
            "matKhau": "SeleniumTest123",
            "matKhau_confirmation": "SeleniumTest123",
        }
        for name, value in values.items():
            replace_input_value(
                self.driver,
                self.driver.find_element(By.NAME, name),
                value,
            )
        birth_date = (date.today() - timedelta(days=365 * 20)).isoformat()
        set_date_input_value(
            self.driver,
            self.driver.find_element(By.NAME, "ngaySinh"),
            birth_date,
        )
        Select(self.driver.find_element(By.NAME, "gioiTinh")).select_by_value("NAM")

    def test_customer_registration_scenarios(self):
        failures = []
        self.open_register_tab()

        # Case 1: the registration form and required fields are visible.
        try:
            required_fields = [
                "hoTen",
                "soDienThoai",
                "email",
                "ngaySinh",
                "gioiTinh",
                "tenDangNhap",
                "matKhau",
                "matKhau_confirmation",
            ]
            visible = all(
                self.driver.find_element(By.NAME, name).is_displayed()
                and self.driver.find_element(By.NAME, name).get_attribute("required")
                is not None
                for name in required_fields
            )
            self.record(
                "Hiển thị form đăng ký",
                visible,
                "Các trường bắt buộc hiển thị"
                if visible
                else "Có trường bắt buộc chưa hiển thị",
            )
            if not visible:
                failures.append("Form đăng ký thiếu trường bắt buộc")
        except Exception as error:
            self.record("Hiển thị form đăng ký", False, str(error))
            failures.append("Không tải được form đăng ký")

        # Case 2: an empty form is blocked by the browser's required validation.
        try:
            self.driver.find_element(*SUBMIT).click()
            empty_blocked = self.driver.execute_script(
                "return [...document.querySelectorAll('form.login-form [required]')].every(input => !input.checkValidity())"
            )
            self.record(
                "Để trống trường bắt buộc",
                empty_blocked and "/register" in self.driver.current_url,
                "Trình duyệt chặn gửi form còn trống"
                if empty_blocked
                else "Form chưa chặn dữ liệu trống",
            )
            if not empty_blocked:
                failures.append("Form đăng ký không chặn trường để trống")
        except Exception as error:
            self.record("Để trống trường bắt buộc", False, str(error))
            failures.append("Không xác nhận được kiểm tra trường trống")

        # Case 3: the application should reject mismatched password confirmation.
        try:
            self.fill_valid_form("selenium-mismatch@example.com", "0912345678")
            replace_input_value(
                self.driver,
                self.driver.find_element(By.NAME, "matKhau_confirmation"),
                "DifferentPassword123",
            )
            self.driver.find_element(*SUBMIT).click()
            error = self.wait.until(
                EC.visibility_of_element_located((By.CSS_SELECTOR, ".login-error"))
            )
            mismatch_rejected = "khớp" in error.text.lower()
            self.record(
                "Mật khẩu xác nhận không khớp",
                mismatch_rejected,
                error.text.strip() or "Không thấy thông báo lỗi",
            )
            if not mismatch_rejected:
                failures.append("Không từ chối mật khẩu xác nhận không khớp")
        except Exception as error:
            self.record("Mật khẩu xác nhận không khớp", False, str(error))
            failures.append("Không xác nhận được kiểm tra mật khẩu không khớp")

        # Case 4: duplicate email is optional; supply an email already in the DB.
        if EXISTING_EMAIL:
            try:
                self.fill_valid_form(EXISTING_EMAIL, "0912345679")
                self.driver.find_element(*SUBMIT).click()
                error = self.wait.until(
                    EC.visibility_of_element_located((By.CSS_SELECTOR, ".login-error"))
                )
                duplicate_rejected = bool(error.text.strip())
                self.record(
                    "Email đã được đăng ký",
                    duplicate_rejected,
                    error.text.strip() or "Không thấy thông báo từ chối email trùng",
                )
                if not duplicate_rejected:
                    failures.append("Email đã tồn tại nhưng không bị từ chối")
            except Exception as error:
                self.record("Email đã được đăng ký", False, str(error))
                failures.append("Không xác nhận được email trùng")
        else:
            self.record(
                "Email đã được đăng ký",
                None,
                "Bỏ qua: đặt CGV_EXISTING_EMAIL bằng email đã có trong cơ sở dữ liệu",
            )

        # Case 5: a successful registration creates a real database account.
        stamp = str(int(time.time()))
        email = os.getenv("CGV_REGISTER_EMAIL", f"selenium{stamp}@gmail.com")
        phone = os.getenv("CGV_REGISTER_PHONE", "0" + stamp[-9:].zfill(9))
        try:
            self.open_register_tab()
            self.fill_valid_form(email, phone)
            invalid_fields = self.driver.execute_script(
                "return [...document.querySelectorAll('form.login-form [required]')]"
                ".filter(input => !input.checkValidity())"
                ".map(input => input.name)"
            )
            if invalid_fields:
                raise AssertionError(
                    "Trường chưa hợp lệ: " + ", ".join(invalid_fields)
                )
            self.driver.find_element(*SUBMIT).click()
            self.wait.until(
                lambda driver: any(
                    element.is_displayed()
                    for element in driver.find_elements(
                        By.CSS_SELECTOR, ".login-error, .login-success"
                    )
                )
                or (
                    driver.find_elements(
                        By.CSS_SELECTOR, ".auth-tabs button.active"
                    )
                    and driver.find_element(
                        By.CSS_SELECTOR, ".auth-tabs button.active"
                    ).text.strip().upper()
                    == "ĐĂNG NHẬP"
                )
            )
            register_errors = self.driver.find_elements(
                By.CSS_SELECTOR, ".login-error"
            )
            visible_error = next(
                (element.text.strip() for element in register_errors if element.is_displayed()),
                "",
            )
            if not visible_error:
                self.wait.until(
                    lambda driver: driver.find_elements(
                        By.CSS_SELECTOR, ".auth-tabs button.active"
                    )
                    and driver.find_element(
                        By.CSS_SELECTOR, ".auth-tabs button.active"
                    ).text.strip().upper()
                    == "ĐĂNG NHẬP"
                )
            login_identifier = self.driver.find_elements(By.NAME, "identifier")
            success = (
                not visible_error
                and login_identifier
                and login_identifier[0].get_attribute("value") == email
            )
            self.record(
                "Đăng ký tài khoản hợp lệ",
                success,
                f"Tạo tài khoản {email}"
                if success
                else visible_error or "Không hoàn tất đăng ký; vẫn ở form đăng ký",
            )
            if not success:
                failures.append(
                    "Đăng ký hợp lệ không hoàn tất: "
                    + (visible_error or "vẫn ở form đăng ký")
                )
        except Exception as error:
            login_errors = self.driver.find_elements(
                By.CSS_SELECTOR, ".login-error"
            )
            detail = (
                login_errors[0].text.strip()
                if login_errors and login_errors[0].is_displayed()
                else f"{type(error).__name__}: {error}"
            )
            self.record("Đăng ký tài khoản hợp lệ", False, detail)
            failures.append(f"Đăng ký hợp lệ thất bại: {detail}")

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
