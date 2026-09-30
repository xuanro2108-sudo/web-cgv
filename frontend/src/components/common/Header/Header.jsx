import { useEffect, useState } from "react";
import { Link, NavLink, useNavigate } from "react-router-dom";
import "./Header.css";

function Header() {
    const navigate = useNavigate();
    const [logoutLoading, setLogoutLoading] = useState(false);

    const [khachHang, setKhachHang] = useState(() => JSON.parse(
        localStorage.getItem("khachHang") || "null"
    ));

    useEffect(() => {
        const updateCustomer = () => setKhachHang(JSON.parse(localStorage.getItem("khachHang") || "null"));
        window.addEventListener("customer-profile-updated", updateCustomer);
        return () => window.removeEventListener("customer-profile-updated", updateCustomer);
    }, []);

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
        setKhachHang(null);
    };

    return (
        <header className="cinema-header">
            <a className="cinema-skip" href="#customer-content">Đi đến nội dung</a>
            <div className="cinema-topline"><span>CGV AEON MALL HÀ ĐÔNG</span><span>Điện ảnh kết nối cảm xúc <i>✦</i></span></div>
            <div className="cinema-navigation">
                <Link to="/home" className="cinema-brand" aria-label="CGV — Trang chủ"><img src="/banners/cgvlogo.png" alt="CGV" /><span>BEYOND THE SCREEN</span></Link>
                <nav aria-label="Điều hướng chính"><NavLink to="/movies">Khám phá phim</NavLink><NavLink to="/my-tickets">Vé của tôi</NavLink><NavLink to="/tin-tuc">Tin tức & ưu đãi</NavLink><NavLink to="/thanh-vien">Thành viên</NavLink></nav>
                <div className="cinema-account">{isLoggedIn ? <><Link to="/thanh-vien" className="cinema-account-name"><span className="cinema-avatar">{khachHang?.hoTen?.trim().slice(0, 1) || "C"}</span><span>{khachHang?.hoTen || "Tài khoản"}</span></Link><button onClick={handleLogout} disabled={logoutLoading}>{logoutLoading ? "Đang thoát…" : "Đăng xuất"}</button></> : <Link className="cinema-login" to="/login">Đăng nhập <span>↗</span></Link>}</div>
            </div>
        </header>
    );
}
export default Header;
