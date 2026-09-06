import { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./HomeCustomer.css";

function HomeCustomer() {
  const navigate = useNavigate();

  // =========================
  // LẤY THÔNG TIN KHÁCH HÀNG
  // =========================
  const khachHang = JSON.parse(
    localStorage.getItem("khachHang") || "null"
  );

  const [logoutLoading, setLogoutLoading] = useState(false);

  // =========================
  // ĐĂNG XUẤT
  // =========================
  const handleLogout = async () => {
    const token = localStorage.getItem("token");

    if (!token) {
      clearLoginData();
      navigate("/");
      return;
    }

    setLogoutLoading(true);

    try {
      const response = await fetch(
        "http://127.0.0.1:8000/api/auth/logout",
        {
          method: "POST",

          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      const data = await response.json();

      if (!response.ok) {
        alert(
          data.message || "Đăng xuất không thành công."
        );

        return;
      }

      // Xóa thông tin đăng nhập ở trình duyệt
      clearLoginData();

      // Chuyển về trang đăng nhập
      navigate("/");

    } catch (error) {
      console.error(
        "Lỗi đăng xuất:",
        error
      );

      alert(
        "Không thể kết nối đến máy chủ."
      );

    } finally {
      setLogoutLoading(false);
    }
  };

  // =========================
  // XÓA LOCAL STORAGE
  // =========================
  const clearLoginData = () => {
    localStorage.removeItem("token");
    localStorage.removeItem("vaiTro");
    localStorage.removeItem("taiKhoan");
    localStorage.removeItem("khachHang");
  };

  return (
    <div className="home-customer">

      {/* =========================
          THANH TRÊN CÙNG
      ========================= */}
      <div className="home-top-bar">

        <div className="home-user">

          <span>
            Xin chào,{" "}
            <strong>
              {khachHang?.hoTen || "Khách hàng"}
            </strong>
          </span>

          <span className="home-separator">
            |
          </span>

          <button
            type="button"
            className="logout-button"
            onClick={handleLogout}
            disabled={logoutLoading}
          >
            {logoutLoading
              ? "ĐANG ĐĂNG XUẤT..."
              : "ĐĂNG XUẤT"}
          </button>

        </div>

      </div>


      {/* =========================
          HEADER
      ========================= */}
      <header className="home-header">

        <div className="home-logo">

          <span className="home-cgv">
            CGV
          </span>

          <span className="home-branch">
            AEON MALL HÀ ĐÔNG
          </span>

        </div>


        <nav className="home-nav">

          <a href="#">
            LỊCH CHIẾU
          </a>

          <a href="#">
            PHIM
          </a>

          <a href="#">
            GIÁ VÉ
          </a>

          <a href="#">
            TIN TỨC & ƯU ĐÃI
          </a>

          <a href="#">
            THÀNH VIÊN
          </a>

        </nav>

      </header>


      {/* =========================
          NỘI DUNG
      ========================= */}
      <main className="home-content">

        <section className="welcome-section">

          <h1>
            Xin chào{" "}
            {khachHang?.hoTen
              ? khachHang.hoTen
              : "khách hàng"}
            !
          </h1>

          <p>
            Chào mừng bạn đến với CGV AEON Mall Hà Đông.
          </p>

        </section>


        <section className="home-placeholder">

          <h2>
            PHIM ĐANG CHIẾU
          </h2>

          <p>
            Danh sách phim sẽ được kết nối API
            ở bước tiếp theo.
          </p>

        </section>

      </main>

    </div>
  );
}

export default HomeCustomer;