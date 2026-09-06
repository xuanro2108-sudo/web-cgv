import { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./HomeCustomer.css";
import Header from "../components/common/Header/Header";

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

    <Header />

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