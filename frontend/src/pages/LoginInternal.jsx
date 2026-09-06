import { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./LoginInternal.css";

function LoginInternal() {
  const navigate = useNavigate();

  const [loginData, setLoginData] = useState({
    tenDangNhap: "",
    matKhau: "",
  });

  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;

    setLoginData({
      ...loginData,
      [name]: value,
    });

    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    setError("");
    setLoading(true);

    try {
      const response = await fetch(
        "http://127.0.0.1:8000/api/auth/internal/login",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },

          body: JSON.stringify({
            tenDangNhap: loginData.tenDangNhap,
            matKhau: loginData.matKhau,
          }),
        }
      );

      const data = await response.json();

      if (!response.ok) {
        setError(
          data.message || "Đăng nhập không thành công."
        );

        return;
      }

      // Lưu token
      localStorage.setItem(
        "token",
        data.token
      );

      // Lưu vai trò
      localStorage.setItem(
        "vaiTro",
        data.taiKhoan.vaiTro
      );

      // Lưu tài khoản
      localStorage.setItem(
        "taiKhoan",
        JSON.stringify(data.taiKhoan)
      );

      // Chuyển sang Dashboard
      navigate("/dashboard");

    } catch (error) {
      console.error(
        "Lỗi đăng nhập nội bộ:",
        error
      );

      setError(
        "Không thể kết nối đến máy chủ."
      );

    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="internal-page">

      <div className="internal-login-box">

        <div className="internal-logo">
          CGV
        </div>

        <p className="internal-branch">
          AEON MALL HÀ ĐÔNG
        </p>

        <h1 className="internal-title">
          ĐĂNG NHẬP HỆ THỐNG
        </h1>

        <p className="internal-description">
          Dành cho Nhân viên và Quản lý
        </p>

        <form
          className="internal-form"
          onSubmit={handleSubmit}
        >

          <label>
            Tên đăng nhập
          </label>

          <input
            type="text"
            name="tenDangNhap"
            placeholder="Nhập tên đăng nhập"
            value={loginData.tenDangNhap}
            onChange={handleChange}
            required
          />

          <label>
            Mật khẩu
          </label>

          <input
            type="password"
            name="matKhau"
            placeholder="Nhập mật khẩu"
            value={loginData.matKhau}
            onChange={handleChange}
            required
          />

          {error && (
            <p className="internal-error">
              {error}
            </p>
          )}

          <button
            type="submit"
            className="internal-login-button"
            disabled={loading}
          >
            {loading
              ? "ĐANG ĐĂNG NHẬP..."
              : "ĐĂNG NHẬP"}
          </button>

        </form>

      </div>

    </div>
  );
}

export default LoginInternal;