import {
    money
} from "../../../utils/counterSaleUtils";

export default function ComboStep({
  combos,
  selectedCombos,
  updateComboQty,
  selectedMovie,
  selectedSeats,
  ticketTotal,
  grandTotal,
  setStep,
}) {
  return (
    <section className="counter-step-view">
      <div className="counter-step-header">
        <div>
          <h2>Chọn Bỏng ngô & Nước uống</h2>
          <p>Thêm combo dịch vụ cho khách hàng (không bắt buộc)</p>
        </div>
        <div className="counter-step-header-actions">
          <button
            type="button"
            className="counter-btn-secondary"
            onClick={() => setStep(3)}
          >
            ← Quay lại chọn ghế
          </button>
          <button
            type="button"
            className="counter-btn-primary"
            onClick={() => setStep(5)}
          >
            Tiếp tục: Thanh toán ({money(grandTotal)}) →
          </button>
        </div>
      </div>

      <div className="counter-combo-layout">
        <div className="counter-combo-grid">
          {combos.length === 0 ? (
            <div className="counter-empty-message">Hiện chưa có combo sản phẩm nào trong hệ thống.</div>
          ) : (
            combos.map((combo) => {
              const qty = selectedCombos[combo.maCombo] || 0;

              return (
                <div className="counter-combo-card" key={combo.maCombo}>
                  <div className="combo-image-wrap">
                    {combo.hinhAnh ? (
                      <img src={combo.hinhAnh} alt={combo.tenCombo} />
                    ) : (
                      <div className="combo-no-img">🍿🥤</div>
                    )}
                  </div>
                  <div className="combo-info">
                    <h3>{combo.tenCombo}</h3>
                    <p className="combo-desc">
                      {combo.moTa || "Bắp ngọt giòn rụm & nước uống mát lạnh"}
                    </p>
                    <div className="combo-price-row">
                      <span className="combo-price">{money(combo.donGia)}</span>
                      <div className="combo-qty-controls">
                        <button
                          type="button"
                          className="qty-btn"
                          onClick={() => updateComboQty(combo.maCombo, -1)}
                          disabled={qty <= 0}
                        >
                          -
                        </button>
                        <span className="qty-val">{qty}</span>
                        <button
                          type="button"
                          className="qty-btn"
                          onClick={() => updateComboQty(combo.maCombo, 1)}
                        >
                          +
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        <aside className="counter-checkout-sidebar">
          <h2>Đơn hàng hiện tại</h2>
          <div className="checkout-summary-box">
            <div className="summary-row">
              <span>Phim</span>
              <strong>{selectedMovie?.tenPhim}</strong>
            </div>
            <div className="summary-row">
              <span>Số lượng vé</span>
              <strong>{selectedSeats.length} ghế ({money(ticketTotal)})</strong>
            </div>
            <div className="summary-divider" />
            <div className="summary-row">
              <strong>Combo chọn:</strong>
            </div>
            {Object.keys(selectedCombos).length === 0 ? (
              <em className="text-muted" style={{ fontSize: "12px" }}>Chưa chọn combo nào</em>
            ) : (
              Object.entries(selectedCombos).map(([maCombo, qty]) => {
                const cb = combos.find((c) => c.maCombo === maCombo);
                return (
                  <div key={maCombo} className="summary-row" style={{ fontSize: "12px" }}>
                    <span>{cb?.tenCombo || maCombo} x{qty}</span>
                    <strong>{money((cb?.donGia || 0) * qty)}</strong>
                  </div>
                );
              })
            )}
            <div className="summary-divider" />
            <div className="summary-total-row">
              <span>TỔNG CỘNG</span>
              <strong className="summary-price">{money(grandTotal)}</strong>
            </div>
          </div>

          <button
            type="button"
            className="counter-btn-submit-order"
            onClick={() => setStep(5)}
          >
            TIẾP TỤC THANH TOÁN →
          </button>
        </aside>
      </div>
    </section>
  );
}
