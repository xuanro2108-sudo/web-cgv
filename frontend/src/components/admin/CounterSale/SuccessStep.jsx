import {
    getSeatLabel,
    money,
    showtimeDate,
    showtimeTime
} from "../../../utils/counterSaleUtils";

export default function SuccessStep({
  completedOrder,
  selectedMovie,
  selectedShowtime,
  selectedDate,
  customer,
  paymentMethod,
  paymentConfig,
  selectedSeats,
  selectedCombos,
  combos,
  grandTotal,
  handleReset,
  navigate,
}) {
  if (!completedOrder) return null;

  const orderTickets = completedOrder.veGhes || completedOrder.ve_ghes || [];
  const orderCombos =
    completedOrder.chiTietComboDonHangs ||
    completedOrder.chi_tiet_combo_don_hangs || [];

  return (
    <section className="counter-step-view counter-success-view">
      <div className="counter-success-card">
        <div className="success-icon-badge">✓</div>
        <h2>Bán vé thành công!</h2>
        <p className="success-subtitle">
          Đơn hàng đã được thanh toán thành công và ghi nhận vào hệ thống.
        </p>

        <div className="printable-receipt">
          <div className="receipt-header">
            <h3>CGV CINEMAS</h3>
            <p>HÓA ĐƠN BÁN VÉ QUẦY POS</p>
            <span className="receipt-order-id">Mã đơn: {completedOrder.maDonHang}</span>
          </div>

          <div className="receipt-divider" />

          <div className="receipt-info-grid">
            <div className="receipt-row">
              <span>Phim:</span>
              <strong>{selectedMovie?.tenPhim || orderTickets[0]?.lichChieu?.phim?.tenPhim}</strong>
            </div>
            <div className="receipt-row">
              <span>Suất chiếu:</span>
              <strong>
                {showtimeTime(selectedShowtime?.gioBatDau || orderTickets[0]?.lichChieu?.gioBatDau)} - {showtimeDate(selectedDate || orderTickets[0]?.lichChieu?.ngayChieu)}
              </strong>
            </div>
            <div className="receipt-row">
              <span>Phòng chiếu:</span>
              <strong>
                {selectedShowtime?.phong_chieu?.tenPhong ||
                  selectedShowtime?.phongChieu?.tenPhong ||
                  orderTickets[0]?.lichChieu?.phongChieu?.tenPhong ||
                  "—"}
              </strong>
            </div>
            <div className="receipt-row">
              <span>Khách hàng:</span>
              <span>
                {completedOrder.khachHang?.hoTen || completedOrder.khach_hang?.hoTen || customer.hoTen} (
                {completedOrder.khachHang?.soDienThoai || completedOrder.khach_hang?.soDienThoai || customer.soDienThoai})
              </span>
            </div>
            <div className="receipt-row">
              <span>Hình thức thanh toán:</span>
              <span>
                {completedOrder.thanhToan?.phuongThuc === "SEPAY_QR" ||
                completedOrder.thanhToan?.phuongThuc === "CHUYEN_KHOAN" ||
                paymentMethod === "SEPAY_QR"
                  ? `Quét mã QR VietQR (${paymentConfig.bank})`
                  : "Tiền mặt tại quầy"}
              </span>
            </div>
          </div>

          <div className="receipt-divider" />

          <div className="receipt-breakdown-section">
            <h4 style={{ margin: "0 0 10px", fontSize: "14px", fontWeight: "700", color: "#374151" }}>
              CHI TIẾT ĐƠN HÀNG
            </h4>

            <div className="receipt-items-group" style={{ marginBottom: "12px" }}>
              <div style={{ fontSize: "12px", fontWeight: "700", color: "#6b7280", marginBottom: "6px" }}>
                VÉ XEM PHIM ({orderTickets.length || selectedSeats.length} ghế)
              </div>
              {orderTickets.length > 0 ? (
                orderTickets.map((t, idx) => (
                  <div className="receipt-item-row" key={t.maVe || idx} style={{ display: "flex", justifyContent: "space-between", padding: "4px 0", fontSize: "13px" }}>
                    <span>Ghế {t.ghe?.hang}{t.ghe?.cot} <small style={{ color: "#6b7280" }}>({t.ghe?.loaiGhe || "THƯỜNG"})</small></span>
                    <strong>{money(t.giaVe)}</strong>
                  </div>
                ))
              ) : (
                selectedSeats.map((seat) => (
                  <div className="receipt-item-row" key={seat.maGhe} style={{ display: "flex", justifyContent: "space-between", padding: "4px 0", fontSize: "13px" }}>
                    <span>Ghế {getSeatLabel(seat)} <small style={{ color: "#6b7280" }}>({seat.loaiGhe || "THƯỜNG"})</small></span>
                    <strong>{money(seat.giaGhe || 0)}</strong>
                  </div>
                ))
              )}
            </div>

            {(orderCombos.length > 0 || Object.keys(selectedCombos).length > 0) && (
              <div className="receipt-items-group">
                <div style={{ fontSize: "12px", fontWeight: "700", color: "#6b7280", marginBottom: "6px" }}>
                  COMBO BẮP NƯỚC DỊCH VỤ
                </div>
                {orderCombos.length > 0 ? (
                  orderCombos.map((line, idx) => (
                    <div className="receipt-item-row" key={line.maChiTiet || idx} style={{ display: "flex", justifyContent: "space-between", padding: "4px 0", fontSize: "13px" }}>
                      <span>{line.combo?.tenCombo || "Combo"} <small style={{ color: "#6b7280" }}>x{line.soLuong}</small></span>
                      <strong>{money(line.thanhTien)}</strong>
                    </div>
                  ))
                ) : (
                  Object.entries(selectedCombos).map(([maCombo, qty]) => {
                    const comboObj = combos.find((c) => c.maCombo === maCombo);
                    if (!comboObj || qty <= 0) return null;
                    return (
                      <div className="receipt-item-row" key={maCombo} style={{ display: "flex", justifyContent: "space-between", padding: "4px 0", fontSize: "13px" }}>
                        <span>{comboObj.tenCombo} <small style={{ color: "#6b7280" }}>x{qty}</small></span>
                        <strong>{money(comboObj.donGia * qty)}</strong>
                      </div>
                    );
                  })
                )}
              </div>
            )}
          </div>

          <div className="receipt-divider" />
          <div className="receipt-total-row">
            <span>TỔNG TIỀN THU:</span>
            <strong>{money(completedOrder.tongTien || grandTotal)}</strong>
          </div>
          <div className="receipt-footer">
            <p>Cảm ơn quý khách và chúc quý khách xem phim vui vẻ!</p>
          </div>
        </div>

        <div className="success-actions">
          <button type="button" className="counter-btn-primary" onClick={() => window.print()}>
            🖨️ In vé / Hóa đơn
          </button>
          <button type="button" className="counter-btn-secondary" onClick={handleReset}>
            + Bán vé đơn tiếp theo
          </button>
          <button type="button" className="counter-btn-outline" onClick={() => navigate("/dashboard/don-hang")}>
            Xem quản lý đơn hàng
          </button>
        </div>

        <div className="om-print-area">
          {orderTickets.length > 0 ? (
            orderTickets.map((ticket) => (
              <article className="om-print-ticket" key={ticket.maVe || ticket.id}>
                <header className="om-ticket-header">
                  <span>CGV CINEMAS</span>
                  <h1>VÉ XEM PHIM</h1>
                  <small>Khách hàng: {completedOrder.khachHang?.hoTen || completedOrder.khach_hang?.hoTen || customer.hoTen || "Khách hàng"}</small>
                </header>
                <div className="om-ticket-perforation" />
                <section className="om-ticket-body">
                  <p className="om-ticket-cinema">
                    CGV · {ticket.lichChieu?.phongChieu?.tenPhong || ticket.lich_chieu?.phong_chieu?.tenPhong || selectedShowtime?.phong_chieu?.tenPhong || selectedShowtime?.phongChieu?.tenPhong || "Phòng chiếu"}
                  </p>
                  <h2>{ticket.lichChieu?.phim?.tenPhim || ticket.lich_chieu?.phim?.tenPhim || selectedMovie?.tenPhim || "Vé xem phim"}</h2>
                  <p className="om-ticket-showtime">
                    {showtimeDate(ticket.lichChieu?.ngayChieu || ticket.lich_chieu?.ngayChieu || selectedDate)} · {showtimeTime(ticket.lichChieu?.gioBatDau || ticket.lich_chieu?.gioBatDau || selectedShowtime?.gioBatDau)}
                  </p>
                  <div className="om-ticket-seat">
                    <span>GHẾ</span>
                    <strong>{ticket.ghe?.hang || ""}{ticket.ghe?.cot || ""}</strong>
                    <span>{ticket.ghe?.loaiGhe || "THƯỜNG"}</span>
                  </div>
                  <p className="om-ticket-price">{money(ticket.giaVe)}</p>
                </section>
                <div className="om-ticket-perforation" />
                <footer className="om-ticket-footer">
                  <p>Mã đơn: {completedOrder.maDonHang}</p>
                  <div className="om-ticket-barcode" aria-label={ticket.maVe}><span>{ticket.maVe}</span></div>
                  <small>Vui lòng xuất trình vé này tại rạp</small>
                </footer>
              </article>
            ))
          ) : (
            selectedSeats.map((seat, idx) => (
              <article className="om-print-ticket" key={seat.maGhe || idx}>
                <header className="om-ticket-header">
                  <span>CGV CINEMAS</span>
                  <h1>VÉ XEM PHIM</h1>
                  <small>Khách hàng: {customer.hoTen || "Khách hàng"}</small>
                </header>
                <div className="om-ticket-perforation" />
                <section className="om-ticket-body">
                  <p className="om-ticket-cinema">CGV · {selectedShowtime?.phong_chieu?.tenPhong || selectedShowtime?.phongChieu?.tenPhong || "Phòng chiếu"}</p>
                  <h2>{selectedMovie?.tenPhim || "Vé xem phim"}</h2>
                  <p className="om-ticket-showtime">{showtimeDate(selectedDate)} · {showtimeTime(selectedShowtime?.gioBatDau)}</p>
                  <div className="om-ticket-seat">
                    <span>GHẾ</span>
                    <strong>{getSeatLabel(seat)}</strong>
                    <span>{seat.loaiGhe || "THƯỜNG"}</span>
                  </div>
                  <p className="om-ticket-price">{money(seat.giaGhe || 0)}</p>
                </section>
                <div className="om-ticket-perforation" />
                <footer className="om-ticket-footer">
                  <p>Mã đơn: {completedOrder.maDonHang}</p>
                  <div className="om-ticket-barcode" aria-label={completedOrder.maDonHang}><span>{completedOrder.maDonHang}-T{idx + 1}</span></div>
                  <small>Vui lòng xuất trình vé này tại rạp</small>
                </footer>
              </article>
            ))
          )}

          {orderCombos.length > 0 ? (
            orderCombos.map((line, idx) => (
              <article className="om-print-combo" key={line.maChiTiet || idx}>
                <header className="om-ticket-header">
                  <span>CGV CINEMAS</span>
                  <h1>PHIẾU NHẬN COMBO</h1>
                  <small>Mã đơn: {completedOrder.maDonHang}</small>
                </header>
                <div className="om-ticket-perforation" />
                <section className="om-ticket-body">
                  <h2>{line.combo?.tenCombo || "Combo bắp nước"}</h2>
                  <div className="om-ticket-seat">
                    <span>SỐ LƯỢNG</span><strong>{line.soLuong}</strong><span>PHẦN</span>
                  </div>
                  <p className="om-ticket-price">{money(line.thanhTien)}</p>
                </section>
                <div className="om-ticket-perforation" />
                <footer className="om-ticket-footer">
                  <div className="om-ticket-barcode" aria-label={line.maChiTiet}><span>{line.maChiTiet || `${completedOrder.maDonHang}-C${idx + 1}`}</span></div>
                  <small>Đổi combo tại quầy bắp nước</small>
                </footer>
              </article>
            ))
          ) : (
            Object.entries(selectedCombos).map(([maCombo, qty], idx) => {
              const comboObj = combos.find((c) => c.maCombo === maCombo);
              if (!comboObj || qty <= 0) return null;
              return (
                <article className="om-print-combo" key={maCombo}>
                  <header className="om-ticket-header">
                    <span>CGV CINEMAS</span>
                    <h1>PHIẾU NHẬN COMBO</h1>
                    <small>Mã đơn: {completedOrder.maDonHang}</small>
                  </header>
                  <div className="om-ticket-perforation" />
                  <section className="om-ticket-body">
                    <h2>{comboObj.tenCombo}</h2>
                    <div className="om-ticket-seat">
                      <span>SỐ LƯỢNG</span><strong>{qty}</strong><span>PHẦN</span>
                    </div>
                    <p className="om-ticket-price">{money(comboObj.donGia * qty)}</p>
                  </section>
                  <div className="om-ticket-perforation" />
                  <footer className="om-ticket-footer">
                    <div className="om-ticket-barcode" aria-label={completedOrder.maDonHang}><span>{completedOrder.maDonHang}-C{idx + 1}</span></div>
                    <small>Đổi combo tại quầy bắp nước</small>
                  </footer>
                </article>
              );
            })
          )}
        </div>
      </div>
    </section>
  );
}
