import {
    money
} from "../../../utils/counterSaleUtils";

export default function PaymentStep({
  customer,
  setCustomer,
  paymentMethod,
  setPaymentMethod,
  paymentConfig,
  pendingOrder,
  selectedMovie,
  selectedDate,
  selectedShowtime,
  selectedSeats,
  ticketTotal,
  comboTotal,
  selectedCombos,
  grandTotal,
  submitting,
  handleCheckout,
  setStep,
}) {
  return (
    <section className="counter-step-view">
      <div className="counter-step-header">
        <div>
          <h2>Thanh toán & Thông tin khách hàng</h2>
          <p>Chọn phương thức thanh toán tiền mặt hoặc quét mã QR</p>
        </div>
        <button
          type="button"
          className="counter-btn-secondary"
          onClick={() => setStep(4)}
        >
          ← Quay lại chọn Combo
        </button>
      </div>

      <div className="counter-payment-layout">
        <div className="counter-payment-main">
          <div className="counter-form-card">
            <h3>1. Thông tin khách hàng</h3>
            <div className="counter-form-grid">
              <label>
                <span>Họ tên khách hàng *</span>
                <input
                  type="text"
                  required
                  placeholder="Nhập họ tên (VD: Nguyễn Văn A)"
                  value={customer.hoTen}
                  onChange={(e) => setCustomer({ ...customer, hoTen: e.target.value })}
                />
              </label>
              <label>
                <span>Số điện thoại *</span>
                <input
                  type="tel"
                  required
                  placeholder="Nhập số điện thoại (VD: 0912345678)"
                  value={customer.soDienThoai}
                  onChange={(e) => setCustomer({ ...customer, soDienThoai: e.target.value })}
                />
              </label>
            </div>
            <p className="form-hint" style={{ marginTop: "10px" }}>
              ℹ️ Hệ thống sẽ tự động tạo tài khoản hoặc tích điểm nếu số điện thoại đã tồn tại.
            </p>
          </div>

          <div className="counter-form-card">
            <h3>2. Phương thức thanh toán</h3>
            <div className="payment-method-options">
              <label className={`payment-method-card ${paymentMethod === "TIEN_MAT" ? "selected" : ""}`}>
                <input
                  type="radio"
                  name="phuongThuc"
                  value="TIEN_MAT"
                  checked={paymentMethod === "TIEN_MAT"}
                  onChange={() => setPaymentMethod("TIEN_MAT")}
                />
                <div className="method-icon">💵</div>
                <div className="method-info">
                  <strong>Tiền mặt tại quầy</strong>
                  <span>Thu tiền trực tiếp từ khách hàng</span>
                </div>
              </label>

              <label className={`payment-method-card ${paymentMethod === "SEPAY_QR" ? "selected" : ""}`}>
                <input
                  type="radio"
                  name="phuongThuc"
                  value="SEPAY_QR"
                  checked={paymentMethod === "SEPAY_QR"}
                  onChange={() => setPaymentMethod("SEPAY_QR")}
                />
                <div className="method-icon">📱</div>
                <div className="method-info">
                  <strong>Quét mã QR / Chuyển khoản VietQR (SePay)</strong>
                  <span>Khách quét mã chuyển khoản qua {paymentConfig.bank}</span>
                </div>
              </label>
            </div>

            {paymentMethod === "SEPAY_QR" && (
              <div className="qr-payment-box">
                {!customer.soDienThoai.trim() ? (
                  <div className="qr-warning-prompt" style={{ padding: "16px", textAlign: "center", color: "#b91c1c" }}>
                    <strong>⚠️ Chưa nhập số điện thoại khách hàng!</strong>
                    <p style={{ margin: "6px 0 0", fontSize: "13px", color: "#4b5563" }}>
                      Vui lòng nhập <strong>Số điện thoại khách hàng</strong> tại Mục 1 ở trên để hệ thống tạo mã QR VietQR chuẩn kèm cú pháp chuyển khoản tự động.
                    </p>
                  </div>
                ) : (
                  <>
                    <div className="qr-code-display">
                      <img
                        src={`https://img.vietqr.io/image/${paymentConfig.bank}-${paymentConfig.accountNumber}-${paymentConfig.template || "compact2"}.png?amount=${grandTotal}&addInfo=${encodeURIComponent(
                          `CGVPOS${customer.soDienThoai.trim().replace(/\D/g, "")}`
                        )}&accountName=${encodeURIComponent(paymentConfig.accountName)}`}
                        alt="Mã QR Chuyển khoản VietQR SePay"
                      />
                      <span className="qr-badge">VietQR · SePay Sync</span>
                    </div>
                    <div className="qr-details">
                      <h4>Thông tin chuyển khoản qua VietQR / SePay:</h4>
                      <p>NGÂN HÀNG: <strong>{paymentConfig.bank}</strong></p>
                      <p>SỐ TÀI KHOẢN: <strong className="highlight-stk">{paymentConfig.accountNumber}</strong></p>
                      <p>CHỦ TÀI KHOẢN: <strong>{paymentConfig.accountName}</strong></p>
                      <p>SỐ TIỀN THU: <strong className="highlight-price">{money(grandTotal)}</strong></p>
                      <p>NỘI DUNG CHUYỂN KHOẢN: <strong>CGVPOS{customer.soDienThoai.trim().replace(/\D/g, "")}</strong></p>
                      <small className="qr-instruction">
                        📌 Nhắc khách quét mã QR VietQR từ app ngân hàng. Hệ thống tự động đối soát SePay.
                      </small>
                    </div>
                  </>
                )}
              </div>
            )}

            {pendingOrder && (
              <div className="sepay-listening-box" style={{ padding: "16px 20px", borderRadius: "14px", background: "#f0f9ff", border: "1px solid #7dd3fc", marginTop: "16px" }}>
                <div style={{ display: "flex", alignItems: "center", justifyContent: "space-between", flexWrap: "wrap", gap: "12px" }}>
                  <div style={{ display: "flex", alignItems: "center", gap: "12px" }}>
                    <span style={{ fontSize: "24px" }}>🔄</span>
                    <div>
                      <strong style={{ color: "#0369a1", fontSize: "15px" }}>Đang tự động chờ tín hiệu từ SePay...</strong>
                      <p style={{ margin: "2px 0 0", fontSize: "13px", color: "#0284c7" }}>
                        Hệ thống đang tự động lắng nghe Webhook SePay. Ngay khi tiền về, màn hình sẽ tự nhảy sang bước In vé.
                      </p>
                    </div>
                  </div>
                  <button
                    type="button"
                    className="counter-btn-secondary"
                    style={{ borderColor: "#0284c7", color: "#0284c7" }}
                    onClick={(e) => handleCheckout(e, true)}
                  >
                    ✓ Xác nhận thủ công
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>

        <aside className="counter-checkout-sidebar">
          <h2>Chi tiết hóa đơn</h2>
          <div className="checkout-summary-box">
            <div className="summary-row"><span>Phim</span><strong>{selectedMovie?.tenPhim}</strong></div>
            <div className="summary-row">
              <span>Suất chiếu</span>
              <strong>{String(selectedShowtime?.gioBatDau).slice(0, 5)} - {selectedDate}</strong>
            </div>
            <div className="summary-row"><span>Ghế</span><strong>{selectedSeats.map((seat) => `${seat.hang || "?"}${seat.cot ?? "?"}`).join(", ")}</strong></div>
            <div className="summary-row"><span>Tiền vé</span><strong>{money(ticketTotal)}</strong></div>
            {comboTotal > 0 && (
              <div className="summary-row">
                <span>Tiền Combo ({Object.values(selectedCombos).reduce((a, b) => a + b, 0)})</span>
                <strong>{money(comboTotal)}</strong>
              </div>
            )}
            <div className="summary-divider" />
            <div className="summary-row">
              <span>Hình thức thanh toán</span>
              <strong style={{ color: "#c62828" }}>
                {paymentMethod === "TIEN_MAT" ? "Tiền mặt tại quầy" : "Quét mã QR (Chuyển khoản)"}
              </strong>
            </div>
            <div className="summary-divider" />
            <div className="summary-total-row">
              <span>TỔNG THANH TOÁN</span>
              <strong className="summary-price">{money(grandTotal)}</strong>
            </div>
          </div>

          <button
            type="button"
            className="counter-btn-submit-order"
            disabled={submitting || !customer.hoTen.trim() || !customer.soDienThoai.trim()}
            onClick={handleCheckout}
          >
            {submitting ? "ĐANG XỬ LÝ..." : `XÁC NHẬN BÁN VÉ · ${money(grandTotal)}`}
          </button>
        </aside>
      </div>
    </section>
  );
}
