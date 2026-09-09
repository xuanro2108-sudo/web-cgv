import { useState } from "react";
import { useNavigate } from "react-router-dom";
import "./Header.css";

function Header() {
    const navigate = useNavigate();
    const [logoutLoading, setLogoutLoading] = useState(false);

    const khachHang = JSON.parse(
        localStorage.getItem("khachHang") || "null"
    );

    const token = localStorage.getItem("token");

const isLoggedIn = Boolean(token && khachHang);

    const handleLogout = async () => {
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
                alert(data.message || "Đăng xuất không thành công.");
                return;
            }

            clearLoginData();
            navigate("/");
        } catch (error) {
            console.error("Lỗi đăng xuất:", error);
            alert("Không thể kết nối đến máy chủ.");
        } finally {
            setLogoutLoading(false);
        }
    };

    const clearLoginData = () => {
        localStorage.removeItem("token");
        localStorage.removeItem("vaiTro");
        localStorage.removeItem("taiKhoan");
        localStorage.removeItem("khachHang");
    };

    return (
        <header className="header">

            {/* Thanh đỏ phía trên */}
            <div className="header-top">
                <div className="header-container header-login">

                    {isLoggedIn ? (
                        <>
                            <span>
                                Xin chào,{" "}
                                <strong>
                                    {khachHang?.hoTen || "Khách hàng"}
                                </strong>
                            </span>

                            <span>|</span>

                            <button
                                type="button"
                                onClick={handleLogout}
                                disabled={logoutLoading}
                            >
                                {logoutLoading
                                    ? "ĐANG ĐĂNG XUẤT..."
                                    : "ĐĂNG XUẤT"}
                            </button>
                        </>
                    ) : (
                        <>
                            <a href="/login">Đăng nhập</a>
                            <span>|</span>
                            <a href="/register">Đăng ký</a>
                        </>
                    )}

                </div>
            </div>

            {/* Logo + Menu */}
            <div className="header-main">
                <div className="header-container header-content">

                    <a href="/home" className="logo">
                        <img
                            className="logo-image"
                            src="/banners/cgvlogo.png"
                            alt="CGV AEON MALL HÀ ĐÔNG"
                        />
                    </a>

                    <nav className="header-menu">

                        <a href="/phim">
                            PHIM
                        </a>
                        <a href="/lich-chieu">
                            VÉ CỦA TÔI
                        </a>

                        <a href="/tin-tuc">
                            TIN TỨC & ƯU ĐÃI
                        </a>

                        <a href="/thanh-vien">
                            THÀNH VIÊN
                        </a>

                    </nav>

                </div>
            </div>

        </header>
    );
}

export default Header;