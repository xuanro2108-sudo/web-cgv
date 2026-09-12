import { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./LoginInternal.css";

function LoginInternal() {
  const navigate = useNavigate();

  const [loginData, setLoginData] = useState({
    email: "",
    matKhau: "",
  });

  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    setLoginData({
      ...loginData,
      [e.target.name]: e.target.value,
    });
    setError("");
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");

    const email = loginData.email.trim().toLowerCase();

    if (!/^[A-Za-z0-9._%+-]+@gmail\.com$/i.test(email)) {
      setError("Email phải có định dạng @gmail.com.");
      return;
    }

    setLoading(true);

    try {
      localStorage.removeItem("token");
      localStorage.removeItem("vaiTro");
      localStorage.removeItem("taiKhoan");
      const response = await fetch(
        `${(import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api").replace(/\/$/, "")}/auth/internal/login`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify({
            email,
            matKhau: loginData.matKhau,
          }),
        }
      );

      const data = await response.json();

      if (!response.ok) {
        setError(data.message || "Đăng nhập không thành công.");
        return;
      }

      localStorage.setItem("token", data.token);
      localStorage.setItem("vaiTro", data.taiKhoan.vaiTro);
      localStorage.setItem("taiKhoan", JSON.stringify(data.taiKhoan));

      navigate("/dashboard");
    } catch {
      setError("Không thể kết nối đến máy chủ.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="internal-page">
      <div className="internal-login-box">
        <div className="internal-logo">CGV</div>

        <p className="internal-branch">AEON MALL HÀ ĐÔNG</p>

        <h1 className="internal-title">ĐĂNG NHẬP HỆ THỐNG</h1>

        <p className="internal-description">
          Dành cho Nhân viên và Quản lý
        </p>

        <form className="internal-form" onSubmit={handleSubmit}>
          <label>Email</label>

          <input
            type="email"
            name="email"
            placeholder="Nhập Email"
            value={loginData.email}
            onChange={handleChange}
            pattern="[A-Za-z0-9._%+\-]+@gmail\.com"
            required
          />

          <label>Mật khẩu</label>

          <input
            type="password"
            name="matKhau"
            placeholder="Nhập mật khẩu"
            value={loginData.matKhau}
            onChange={handleChange}
            required
          />

          {error && <p className="internal-error">{error}</p>}

          <button
            type="submit"
            className="internal-login-button"
            disabled={loading}
          >
            {loading ? "ĐANG ĐĂNG NHẬP..." : "ĐĂNG NHẬP"}
          </button>
        </form>
      </div>
    </div>
  );
}

export default LoginInternal;