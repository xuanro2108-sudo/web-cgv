import { Link } from "react-router-dom";
import "./Footer.css";

export default function Footer() {
    return <footer className="cinema-footer">
        <div className="cinema-footer-grid"><div><img src="/banners/cgvlogo.png" alt="CGV" /><p>Nơi những câu chuyện trở nên sống động.<br />AEON MALL Hà Đông, Hà Nội.</p></div><div><h3>KHÁM PHÁ</h3><Link to="/movies">Phim & lịch chiếu</Link><Link to="/tin-tuc">Tin tức & ưu đãi</Link></div><div><h3>GÓC CỦA BẠN</h3><Link to="/my-tickets">Vé của tôi</Link><Link to="/thanh-vien">Thông tin thành viên</Link></div></div>
        <div className="cinema-footer-bottom"><span>© {new Date().getFullYear()} CGV AEON MALL Hà Đông</span><span>BEYOND THE SCREEN ✦</span></div>
    </footer>;
}
