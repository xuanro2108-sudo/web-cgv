"""Visible Selenium checks for movie browsing, showtimes, and seat selection."""

import os
import time
import unittest
from datetime import date

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait


BASE_URL = os.getenv("CGV_BASE_URL", "http://localhost:5173")
IDENTIFIER = os.getenv("CGV_TEST_IDENTIFIER", "")
PASSWORD = os.getenv("CGV_TEST_PASSWORD", "")


class MovieBookingUiTest(unittest.TestCase):
    def setUp(self):
        options = webdriver.ChromeOptions()
        options.add_argument("--window-size=1365,900")
        self.driver = webdriver.Chrome(options=options)
        self.wait = WebDriverWait(self.driver, 15)
        self.driver.get(f"{BASE_URL}/movies")
        self.failures = []

    def record(self, name, status, detail):
        self.driver.execute_script(
            """
            (function (result) {
              let panel = document.getElementById('selenium-test-results');
              if (!panel) {
                panel = document.createElement('aside');
                panel.id = 'selenium-test-results';
                Object.assign(panel.style, {
                  position: 'fixed', right: '16px', top: '16px', zIndex: '999999',
                  width: '380px', maxHeight: '72vh', overflow: 'auto',
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

    def skip_booking_cases(self, reason):
        for name in [
            "Chọn suất chiếu và phòng chiếu",
            "Chọn ghế thường đang hoạt động",
            "Chọn ghế VIP",
            "Chọn ghế đôi",
            "Không cho chọn ghế đã bán",
            "Không cho đặt ghế bị khóa",
        ]:
            self.record(name, "SKIP", reason)

    def test_movie_and_seat_scenarios(self):
        # FR-16: search customer movies by title and handle empty results.
        try:
            self.wait.until(
                lambda driver: driver.find_elements(By.CSS_SELECTOR, ".movie-card")
                or driver.find_elements(By.CSS_SELECTOR, ".movies-error, .movie-message")
            )
            movie_titles = [
                title.text.strip()
                for title in self.driver.find_elements(
                    By.CSS_SELECTOR, ".movie-card h3"
                )
                if title.text.strip()
            ]
            search_field = self.driver.find_element(
                By.CSS_SELECTOR, "input[aria-label='Tìm phim']"
            )
            if movie_titles:
                search_field.send_keys(movie_titles[0])
                self.wait.until(
                    lambda driver: driver.find_elements(
                        By.CSS_SELECTOR, ".movie-card h3"
                    )
                )
                filtered_titles = [
                    title.text.strip()
                    for title in self.driver.find_elements(
                        By.CSS_SELECTOR, ".movie-card h3"
                    )
                ]
                found_title = any(title == movie_titles[0] for title in filtered_titles)
                self.record(
                    "Tìm kiếm phim theo tên",
                    "PASS" if found_title else "FAIL",
                    f"Tìm thấy phim: {movie_titles[0]}"
                    if found_title
                    else "Không thấy phim đã nhập trong kết quả.",
                )
                if not found_title:
                    self.failures.append("Tìm kiếm tên phim không trả về phim tương ứng")

                search_field.clear()
                search_field.send_keys("__selenium_khong_co_phim_999999__")
                no_results = self.wait.until(
                    lambda driver: bool(
                        driver.find_elements(By.CSS_SELECTOR, ".movie-message")
                    )
                    and not driver.find_elements(By.CSS_SELECTOR, ".movie-card")
                )
                self.record(
                    "Tìm kiếm phim không có kết quả",
                    "PASS" if no_results else "FAIL",
                    "Hiện thông báo khi không có phim phù hợp."
                    if no_results
                    else "Không hiện trạng thái không có kết quả.",
                )
                if not no_results:
                    self.failures.append("Tìm kiếm không có kết quả chưa hiển thị đúng")
            else:
                self.record(
                    "Tìm kiếm phim theo tên",
                    "SKIP",
                    "Danh sách hiện không có phim để làm dữ liệu tìm kiếm.",
                )
        except Exception as error:
            self.record("Tải danh sách phim", "FAIL", str(error))
            self.failures.append("Không tải được trang danh sách phim")
            self.skip_booking_cases("Không tải được danh sách phim")

        if not IDENTIFIER or not PASSWORD:
            self.skip_booking_cases(
                "Cần đặt CGV_TEST_IDENTIFIER và CGV_TEST_PASSWORD cho tài khoản khách hàng thử nghiệm."
            )
        else:
            try:
                self.driver.get(f"{BASE_URL}/login")
                self.wait.until(
                    EC.visibility_of_element_located((By.NAME, "identifier"))
                ).send_keys(IDENTIFIER)
                self.driver.find_element(By.NAME, "matKhau").send_keys(PASSWORD)
                self.driver.find_element(
                    By.CSS_SELECTOR, "form.login-form button[type='submit']"
                ).click()
                self.wait.until(EC.url_contains("/home"))

                showtime_data = self.driver.execute_async_script(
                    """
                    const done = arguments[arguments.length - 1];
                    fetch('http://127.0.0.1:8000/api/lich-chieus', {
                      headers: { Accept: 'application/json' }
                    }).then(response => response.json())
                      .then(payload => done(payload.data || []))
                      .catch(() => done([]));
                    """
                )
                candidate = next(
                    (
                        item
                        for item in showtime_data
                        if item.get("trangThai") == "HOAT_DONG"
                        and item.get("ngayChieu")
                        and item.get("gioBatDau")
                    ),
                    None,
                )
                if not candidate:
                    self.skip_booking_cases(
                        "Không có suất chiếu hoạt động trong dữ liệu trả về để kiểm thử."
                    )
                else:
                    self.driver.get(f"{BASE_URL}/dat-ve/{candidate['maPhim']}")
                    self.wait.until(
                        EC.presence_of_element_located(
                            (By.CSS_SELECTOR, ".showtimes-dates button")
                        )
                    )
                    show_date = str(candidate["ngayChieu"])[:10]
                    target_date = date.fromisoformat(show_date).strftime("%d/%m")
                    date_buttons = self.driver.find_elements(
                        By.CSS_SELECTOR, ".showtimes-dates button"
                    )
                    matching_date = next(
                        (
                            button
                            for button in date_buttons
                            if target_date
                            in "".join(button.text.split())
                        ),
                        None,
                    )
                    if matching_date:
                        matching_date.click()
                    self.wait.until(
                        EC.presence_of_element_located(
                            (By.CSS_SELECTOR, ".showtime-button")
                        )
                    )
                    matching_time = next(
                        (
                            button
                            for button in self.driver.find_elements(
                                By.CSS_SELECTOR, ".showtime-button"
                            )
                            if button.find_element(By.TAG_NAME, "strong").text.startswith(
                                str(candidate["gioBatDau"])[:5]
                            )
                        ),
                        None,
                    )
                    if not matching_time:
                        raise AssertionError("Không tìm thấy suất chiếu trên giao diện.")
                    matching_time.click()
                    self.wait.until(
                        EC.presence_of_element_located(
                            (By.CSS_SELECTOR, ".seat-map-panel .seat-rows")
                        )
                    )
                    seat_buttons = self.driver.find_elements(
                        By.CSS_SELECTOR, ".seat-map-panel button.seat"
                    )
                    if seat_buttons:
                        self.record(
                            "Chọn suất chiếu và phòng chiếu",
                            "PASS",
                            "Mở được sơ đồ ghế của suất chiếu đã chọn.",
                        )
                    else:
                        self.record(
                            "Chọn suất chiếu và phòng chiếu",
                            "FAIL",
                            "Sơ đồ phòng không hiển thị ghế.",
                        )
                        self.failures.append("Sơ đồ ghế trống")

                    self.check_selectable_seat(
                        "Chọn ghế thường đang hoạt động", ".seat.free.thuong"
                    )
                    self.check_selectable_seat("Chọn ghế VIP", ".seat.free.vip")
                    self.check_selectable_seat(
                        "Chọn ghế đôi", ".seat.free.doi.couple"
                    )
                    occupied = self.driver.find_elements(
                        By.CSS_SELECTOR, ".seat.occupied"
                    )
                    if occupied:
                        disabled = all(button.get_property("disabled") for button in occupied)
                        self.record(
                            "Không cho chọn ghế đã bán",
                            "PASS" if disabled else "FAIL",
                            "Các ghế đang bị chiếm đều bị vô hiệu hóa."
                            if disabled
                            else "Có ghế đang bị chiếm nhưng vẫn bấm được.",
                        )
                        if not disabled:
                            self.failures.append("Có thể bấm ghế đang bị chiếm")
                        self.record(
                            "Không cho đặt ghế bị khóa",
                            "SKIP",
                            "Giao diện gom ghế khóa và ghế đã bán thành cùng trạng thái; không xác định riêng ghế khóa.",
                        )
                    else:
                        self.record(
                            "Không cho chọn ghế đã bán",
                            "SKIP",
                            "Suất chiếu này không có ghế đã bán để kiểm thử.",
                        )
                        self.record(
                            "Không cho đặt ghế bị khóa",
                            "SKIP",
                            "Suất chiếu này không có ghế không khả dụng để kiểm thử.",
                        )
            except Exception as error:
                self.record("Luồng chọn suất/ghế", "FAIL", str(error))
                self.failures.append(f"Luồng chọn suất/ghế lỗi: {error}")
                self.skip_booking_cases("Luồng đặt vé không đi tới được sơ đồ ghế.")

        self.driver.execute_script(
            """const panel = document.getElementById('selenium-test-results');
               if (panel) { const note = document.createElement('p');
                 note.textContent = 'Đã chạy xong. Nhấn Enter trong cửa sổ terminal để đóng trình duyệt.';
                 panel.prepend(note); }"""
        )
        input("Đã hiện kết quả trên trình duyệt. Nhấn Enter để đóng Chrome...")
        if self.failures:
            self.fail("; ".join(self.failures))

    def check_selectable_seat(self, name, selector):
        seats = self.driver.find_elements(By.CSS_SELECTOR, selector)
        if not seats:
            self.record(name, "SKIP", "Sơ đồ phòng này không có ghế phù hợp.")
            return

        seat = next((item for item in seats if item.is_displayed() and item.is_enabled()), None)
        if seat is None:
            self.record(name, "SKIP", "Không có ghế khả dụng thuộc loại này.")
            return

        seat.click()
        self.wait.until(lambda _driver: "selected" in seat.get_attribute("class").split())
        selected = "selected" in seat.get_attribute("class").split()
        self.record(
            name,
            "PASS" if selected else "FAIL",
            f"Ghế {seat.text} chuyển sang trạng thái đang chọn."
            if selected
            else "Ghế không chuyển sang trạng thái đang chọn.",
        )
        if not selected:
            self.failures.append(f"{name}: ghế không được chọn")
            return
        seat.click()
        self.wait.until(lambda _driver: "selected" not in seat.get_attribute("class").split())

    def tearDown(self):
        if hasattr(self, "driver"):
            self.driver.quit()


if __name__ == "__main__":
    unittest.main(verbosity=2)
