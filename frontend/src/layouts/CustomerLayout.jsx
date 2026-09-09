import Header from "../components/common/Header/Header";
import Footer from "../components/common/Footer/Footer";

function CustomerLayout({ children }) {
  return (
    <div className="customer-layout">
      <Header />

      <main>
        {children}
      </main>

      <Footer />
    </div>
  );
}

export default CustomerLayout;