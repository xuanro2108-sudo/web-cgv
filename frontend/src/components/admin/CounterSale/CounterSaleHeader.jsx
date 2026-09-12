export default function CounterSaleHeader({ navigate }) {
  return (
    <header className="counter-sale-header">
      <div className="counter-header-info">
        <span className="counter-badge">QUẦY THU NGÂN POS</span>
        <h1>Bán vé tại quầy</h1>
        <p>Quy trình chọn vé, combo bỏng nước và thanh toán trực tiếp cho khách</p>
      </div>
      <div className="counter-header-actions">
        <button
          type="button"
          className="counter-btn-secondary"
          onClick={() => navigate("/dashboard/don-hang")}
        >
          📋 Quản lý đơn hàng
        </button>
      </div>
    </header>
  );
}
