import { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./LoginCustomer.css";

function LoginCustomer() {
  const navigate = useNavigate();

  // =========================
  // NGÀY HIỆN TẠI
  // Dùng làm ngày sinh tối đa
  // =========================
  const today = new Date();

  const maxBirthDate =
    today.getFullYear() +
    "-" +
    String(today.getMonth() + 1).padStart(2, "0") +
    "-" +
    String(today.getDate()).padStart(2, "0");

  // =========================
  // TAB LOGIN / REGISTER
  // =========================
  const [activeTab, setActiveTab] = useState("login");

  // =========================
  // DỮ LIỆU ĐĂNG NHẬP
  // =========================
  const [loginData, setLoginData] = useState({
    identifier: "",
    matKhau: "",
  });

  const [loginMessage, setLoginMessage] = useState("");
  const [loginError, setLoginError] = useState("");
  const [loginLoading, setLoginLoading] = useState(false);

  // =========================
  // DỮ LIỆU ĐĂNG KÝ
  // =========================
  const [registerData, setRegisterData] = useState({
    hoTen: "",
    soDienThoai: "",
    email: "",
    ngaySinh: "",
    gioiTinh: "",
    matKhau: "",
    matKhau_confirmation: "",
  });

  const [registerMessage, setRegisterMessage] = useState("");
  const [registerError, setRegisterError] = useState("");
  const [registerLoading, setRegisterLoading] = useState(false);

  // =========================
  // NHẬP DỮ LIỆU LOGIN
  // =========================
  const handleLoginChange = (e) => {
    const { name, value } = e.target;

    setLoginData({
      ...loginData,
      [name]: value,
    });

    setLoginError("");
    setLoginMessage("");
  };

  // =========================
  // NHẬP DỮ LIỆU REGISTER
  // =========================
  const handleRegisterChange = (e) => {
    const { name, value } = e.target;

    // Số điện thoại chỉ cho nhập số
    if (name === "soDienThoai") {
      const onlyNumbers = value.replace(/\D/g, "");

      setRegisterData({
        ...registerData,
        soDienThoai: onlyNumbers.slice(0, 10),
      });

      setRegisterError("");
      setRegisterMessage("");

      return;
    }

    setRegisterData({
      ...registerData,
      [name]: value,
    });

    setRegisterError("");
    setRegisterMessage("");
  };

  // =========================
  // ĐĂNG NHẬP
  // =========================
  const handleLoginSubmit = async (e) => {
    e.preventDefault();

    setLoginError("");
    setLoginMessage("");
    setLoginLoading(true);

    try {
      const response = await fetch(
        "http://127.0.0.1:8000/api/auth/customer/login",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },

          body: JSON.stringify({
            identifier: loginData.identifier.trim(),
            matKhau: loginData.matKhau,
          }),
        }
      );

      const data = await response.json();

      if (!response.ok) {
        setLoginError(
          data.message || "Đăng nhập không thành công."
        );

        return;
      }

      // =========================
      // LƯU TOKEN
      // =========================
      localStorage.setItem(
        "token",
        data.token
      );

      localStorage.setItem(
        "vaiTro",
        data.taiKhoan.vaiTro
      );

      localStorage.setItem(
        "taiKhoan",
        JSON.stringify(data.taiKhoan)
      );

      if (data.khachHang) {
        localStorage.setItem(
          "khachHang",
          JSON.stringify(data.khachHang)
        );
      }

      setLoginMessage(
        "Đăng nhập thành công!"
      );

      // Chuyển sang trang chủ
      navigate("/home");

    } catch (error) {
      console.error(
        "Lỗi đăng nhập:",
        error
      );

      setLoginError(
        "Không thể kết nối đến máy chủ."
      );

    } finally {
      setLoginLoading(false);
    }
  };

  // =========================
  // ĐĂNG KÝ
  // =========================
  const handleRegisterSubmit = async (e) => {
    e.preventDefault();

    setRegisterError("");
    setRegisterMessage("");

    // =========================
    // KIỂM TRA SĐT
    // =========================
    const phoneRegex = /^0[0-9]{9}$/;

    if (!phoneRegex.test(registerData.soDienThoai)) {
      setRegisterError(
        "Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0."
      );

      return;
    }

    // =========================
    // KIỂM TRA GMAIL
    // =========================
    const gmailRegex =
      /^[A-Za-z0-9._%+-]+@gmail\.com$/i;

    if (!gmailRegex.test(registerData.email.trim())) {
      setRegisterError(
        "Email phải có định dạng @gmail.com."
      );

      return;
    }

    // =========================
    // KIỂM TRA NGÀY SINH
    // =========================
    if (
      registerData.ngaySinh &&
      registerData.ngaySinh > maxBirthDate
    ) {
      setRegisterError(
        "Ngày sinh không được lớn hơn ngày hiện tại."
      );

      return;
    }

    // =========================
    // KIỂM TRA MẬT KHẨU
    // =========================
    if (registerData.matKhau.length < 6) {
      setRegisterError(
        "Mật khẩu phải có ít nhất 6 ký tự."
      );

      return;
    }

    if (
      registerData.matKhau !==
      registerData.matKhau_confirmation
    ) {
      setRegisterError(
        "Mật khẩu xác nhận không khớp."
      );

      return;
    }

    setRegisterLoading(true);

    try {
      const dataToSend = {
        ...registerData,
        email: registerData.email
          .trim()
          .toLowerCase(),
      };

      const response = await fetch(
        "http://127.0.0.1:8000/api/auth/register",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },

          body: JSON.stringify(dataToSend),
        }
      );

      const data = await response.json();

      // =========================
      // ĐĂNG KÝ LỖI
      // =========================
      if (!response.ok) {
        if (data.errors) {
          const firstError =
            Object.values(data.errors)[0];

          setRegisterError(
            Array.isArray(firstError)
              ? firstError[0]
              : firstError
          );
        } else {
          setRegisterError(
            data.message ||
              "Đăng ký không thành công."
          );
        }

        return;
      }

      // =========================
      // ĐĂNG KÝ THÀNH CÔNG
      // =========================
      setRegisterMessage(
        "Đăng ký tài khoản thành công!"
      );

      // Điền sẵn email vào form đăng nhập
      setLoginData({
        identifier: registerData.email
          .trim()
          .toLowerCase(),
        matKhau: "",
      });

      // Xóa form đăng ký
      setRegisterData({
        hoTen: "",
        soDienThoai: "",
        email: "",
        ngaySinh: "",
        gioiTinh: "",
        matKhau: "",
        matKhau_confirmation: "",
      });

      // Chuyển về tab đăng nhập
      setTimeout(() => {
        setActiveTab("login");

        setLoginMessage(
          "Đăng ký thành công. Hãy đăng nhập!"
        );

        setRegisterMessage("");
      }, 1000);

    } catch (error) {
      console.error(
        "Lỗi đăng ký:",
        error
      );

      setRegisterError(
        "Không thể kết nối đến máy chủ."
      );

    } finally {
      setRegisterLoading(false);
    }
  };

  return (
    <div className="customer-page">

      {/* =========================
          HEADER
      ========================= */}
      <header className="customer-header">

        <div className="header-top">

          <div className="header-top-right">

            <span
              onClick={() =>
                setActiveTab("login")
              }
            >
              Đăng nhập
            </span>

            <span>|</span>

            <span
              onClick={() =>
                setActiveTab("register")
              }
            >
              Đăng ký
            </span>

          </div>

        </div>

        <div className="header-main">

          <div className="cgv-logo">

            <span className="cgv-name">
              CGV
            </span>

            <span className="cgv-branch">
              AEON MALL HÀ ĐÔNG
            </span>

          </div>

          <nav className="customer-nav">

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

        </div>

      </header>


      {/* =========================
          LOGIN / REGISTER
      ========================= */}
      <main className="login-page">

        <div className="login-box">

          {/* TAB */}
          <div className="auth-tabs">

            <button
              type="button"
              className={
                activeTab === "login"
                  ? "auth-tab active"
                  : "auth-tab"
              }
              onClick={() =>
                setActiveTab("login")
              }
            >
              ĐĂNG NHẬP
            </button>

            <button
              type="button"
              className={
                activeTab === "register"
                  ? "auth-tab active"
                  : "auth-tab"
              }
              onClick={() =>
                setActiveTab("register")
              }
            >
              ĐĂNG KÝ
            </button>

          </div>


          {/* =========================
              FORM LOGIN
          ========================= */}
          {activeTab === "login" && (

            <div>

              <h1 className="login-title">
                ĐĂNG NHẬP KHÁCH HÀNG
              </h1>

              <form
                className="login-form"
                onSubmit={handleLoginSubmit}
              >

                <label>
                  Email hoặc số điện thoại
                </label>

                <input
                  type="text"
                  name="identifier"
                  placeholder="Nhập email hoặc số điện thoại"
                  value={loginData.identifier}
                  onChange={handleLoginChange}
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
                  onChange={handleLoginChange}
                  required
                />


                <a
                  href="#"
                  className="forgot-password"
                >
                  Quên mật khẩu?
                </a>


                {loginError && (
                  <p className="login-error">
                    {loginError}
                  </p>
                )}


                {loginMessage && (
                  <p className="login-success">
                    {loginMessage}
                  </p>
                )}


                <button
                  type="submit"
                  className="login-button"
                  disabled={loginLoading}
                >
                  {loginLoading
                    ? "ĐANG ĐĂNG NHẬP..."
                    : "ĐĂNG NHẬP"}
                </button>

              </form>

            </div>

          )}


          {/* =========================
              FORM REGISTER
          ========================= */}
          {activeTab === "register" && (

            <div>

              <h1 className="login-title">
                ĐĂNG KÝ KHÁCH HÀNG
              </h1>

              <form
                className="login-form"
                onSubmit={handleRegisterSubmit}
              >

                {/* HỌ TÊN */}
                <label>
                  Họ và tên
                </label>

                <input
                  type="text"
                  name="hoTen"
                  placeholder="Nhập họ và tên"
                  value={registerData.hoTen}
                  onChange={handleRegisterChange}
                  required
                />


                {/* SỐ ĐIỆN THOẠI */}
                <label>
                  Số điện thoại
                </label>

                <input
                  type="text"
                  name="soDienThoai"
                  placeholder="Ví dụ: 0987654321"
                  value={registerData.soDienThoai}
                  onChange={handleRegisterChange}
                  maxLength="10"
                  pattern="0[0-9]{9}"
                  title="Số điện thoại phải gồm 10 chữ số và bắt đầu bằng số 0"
                  required
                />


                {/* EMAIL */}
                <label>
                  Email
                </label>

                <input
                  type="email"
                  name="email"
                  placeholder="Ví dụ: example@gmail.com"
                  value={registerData.email}
                  onChange={handleRegisterChange}
                  pattern="[A-Za-z0-9._%+-]+@gmail\.com"
                  title="Email phải có định dạng @gmail.com"
                  required
                />


                {/* NGÀY SINH */}
                <label>
                  Ngày sinh
                </label>

                <input
                  type="date"
                  name="ngaySinh"
                  value={registerData.ngaySinh}
                  onChange={handleRegisterChange}
                  max={maxBirthDate}
                />


                {/* GIỚI TÍNH */}
                <label>
                  Giới tính
                </label>

                <select
                  name="gioiTinh"
                  value={registerData.gioiTinh}
                  onChange={handleRegisterChange}
                >

                  <option value="">
                    Chọn giới tính
                  </option>

                  <option value="NAM">
                    Nam
                  </option>

                  <option value="NU">
                    Nữ
                  </option>

                </select>


                {/* MẬT KHẨU */}
                <label>
                  Mật khẩu
                </label>

                <input
                  type="password"
                  name="matKhau"
                  placeholder="Tối thiểu 6 ký tự"
                  value={registerData.matKhau}
                  onChange={handleRegisterChange}
                  minLength="6"
                  required
                />


                {/* XÁC NHẬN MẬT KHẨU */}
                <label>
                  Xác nhận mật khẩu
                </label>

                <input
                  type="password"
                  name="matKhau_confirmation"
                  placeholder="Nhập lại mật khẩu"
                  value={
                    registerData.matKhau_confirmation
                  }
                  onChange={handleRegisterChange}
                  minLength="6"
                  required
                />


                {registerError && (
                  <p className="login-error">
                    {registerError}
                  </p>
                )}


                {registerMessage && (
                  <p className="login-success">
                    {registerMessage}
                  </p>
                )}


                <button
                  type="submit"
                  className="login-button"
                  disabled={registerLoading}
                >
                  {registerLoading
                    ? "ĐANG ĐĂNG KÝ..."
                    : "ĐĂNG KÝ"}
                </button>

              </form>

            </div>

          )}

        </div>

      </main>

    </div>
  );
}

export default LoginCustomer;