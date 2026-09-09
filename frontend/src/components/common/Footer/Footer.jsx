import "./Footer.css";

function Footer() {
    return (
        <footer className="footer">
            <div className="footer-container">

                <div className="footer-column">
                    <h3>CGV AEON MALL HÀ ĐÔNG</h3>
                    <p>
                        Hệ thống đặt vé xem phim trực tuyến.
                    </p>
                    <p>
                        Địa chỉ: AEON MALL Hà Đông, Hà Nội
                    </p>
                </div>

                <div className="footer-column">
                    <h3>LIÊN KẾT</h3>
                    <a href="/phim">Phim</a>
                    <a href="/lich-chieu">Lịch chiếu</a>
                    <a href="/tin-tuc">Tin tức & Ưu đãi</a>
                    <a href="/thanh-vien">Thành viên</a>
                </div>

                <div className="footer-column">
                    <h3>HỖ TRỢ</h3>
                    <p>Điều khoản sử dụng</p>
                    <p>Chính sách bảo mật</p>
                    <p>Liên hệ</p>
                </div>

            </div>

            <div className="footer-bottom">
                © 2026 CGV AEON MALL HÀ ĐÔNG. All rights reserved.
            </div>
        </footer>
    );
}

export default Footer;