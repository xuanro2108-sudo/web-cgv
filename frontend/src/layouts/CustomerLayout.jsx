import Header from "../components/common/Header/Header";
import Footer from "../components/common/Footer/Footer";
import { useEffect } from "react";
import { useLocation } from "react-router-dom";

function CustomerLayout({ children }) {
  const { pathname } = useLocation();
  const bookingStep = pathname.startsWith("/dat-ve/") ? 0
    : pathname.startsWith("/chon-ghe/") ? 1
      : /\/(chon-combo|thanh-toan)\//.test(pathname) ? 2 : -1;

  useEffect(() => {
    window.scrollTo(0, 0);
  }, [pathname]);

  return (
    <div className="customer-layout">
      <Header />

      <main id="customer-content">
        {bookingStep >= 0 && <nav className="cinema-booking-progress" aria-label="Tiến trình đặt vé">
          {["Chọn suất chiếu", "Chọn ghế", "Combo & thanh toán"].map((label, index) => (
            <div key={label} className={index <= bookingStep ? "reached" : ""} aria-current={index === bookingStep ? "step" : undefined}>
              <span>{index < bookingStep ? "✓" : `0${index + 1}`}</span><strong>{label}</strong>
            </div>
          ))}
        </nav>}
        {children}
      </main>

      <Footer />
    </div>
  );
}

export default CustomerLayout;
