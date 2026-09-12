import {
    getSeatLabel,
    money
} from "../../../utils/counterSaleUtils";

export default function SeatStep({
  selectedMovie,
  selectedDate,
  selectedShowtime,
  setStep,
  seatGroups,
  selectedSeats,
  toggleSeatGroup,
  seatPrices,
  ticketTotal,
}) {
  return (
    <section className="counter-step-view counter-seat-booking-view">
      <div className="counter-seat-nav-bar">
        <div>
          <strong>{selectedMovie?.tenPhim}</strong> ·{" "}
          <span>
            {selectedDate} · {String(selectedShowtime.gioBatDau).slice(0, 5)} ·{" "}
            {selectedShowtime.phong_chieu?.tenPhong ||
              selectedShowtime.phongChieu?.tenPhong ||
              "Phòng chiếu"}
          </span>
        </div>
        <button
          type="button"
          className="counter-btn-change"
          onClick={() => setStep(2)}
        >
          ← Chọn suất khác
        </button>
      </div>

      <div className="counter-booking-layout">
        <main className="counter-seat-map-panel">
          <div className="seat-legend">
            <span><i className="seat free" /> Ghế thường</span>
            <span><i className="seat vip" /> Ghế VIP</span>
            <span><i className="seat doi couple" /> Ghế đôi</span>
            <span><i className="seat selected" /> Đang chọn</span>
            <span><i className="seat occupied" /> Đã bán</span>
            <span><i className="seat used" /> Đã sử dụng</span>
          </div>

          <div className="screen-banner">
            <span>MÀN HÌNH CHIẾU</span>
          </div>

          <div className="seat-grid-container">
            {seatGroups.map(([row, groups]) => (
              <div className="counter-seat-row" key={row}>
                <span className="row-label">{row}</span>
                <div className="row-seats">
                  {groups.map((seatGroup) => {
                    const isSelected = seatGroup.every((seat) =>
                      selectedSeats.some((item) => item.maGhe === seat.maGhe)
                    );
                    const isUsed = seatGroup.some(
                      (seat) => seat.trangThai === "DA_SU_DUNG"
                    );
                    const isOccupied = seatGroup.some(
                      (seat) => seat.trangThai !== "HOAT_DONG"
                    );
                    const firstSeat = seatGroup[0];
                    const seatStatusClass = isUsed
                      ? "used occupied"
                      : isOccupied
                      ? "occupied"
                      : "free";
                    const seatTitle = isUsed
                      ? "Ghế đã sử dụng"
                      : isOccupied
                      ? "Ghế đã bán"
                      : seatGroup.map(getSeatLabel).join(" - ");

                    return (
                      <button
                        type="button"
                        key={seatGroup.map((seat) => seat.maGhe).join("-")}
                        className={`seat ${firstSeat.loaiGhe.toLowerCase()} ${
                          seatGroup.length > 1 ? "couple" : ""
                        } ${isSelected ? "selected" : ""} ${seatStatusClass}`}
                        onClick={() => toggleSeatGroup(seatGroup)}
                        disabled={isOccupied}
                        title={seatTitle}
                      >
                        {seatGroup.map(getSeatLabel).join(" - ")}
                      </button>
                    );
                  })}
                </div>
                <span className="row-label">{row}</span>
              </div>
            ))}
          </div>

          <div className="seat-pricing-footer">
            <span>Thường: <strong>{money(seatPrices.THUONG)}</strong></span>
            <span>VIP: <strong>{money(seatPrices.VIP)}</strong></span>
            <span>Đôi: <strong>{money(seatPrices.DOI)}</strong></span>
          </div>
        </main>

        <aside className="counter-checkout-sidebar">
          <h2>Thông tin vé chọn</h2>
          <div className="checkout-summary-box">
            <div className="summary-row">
              <span>Phim</span>
              <strong>{selectedMovie?.tenPhim}</strong>
            </div>
            <div className="summary-row">
              <span>Suất chiếu</span>
              <strong>
                {String(selectedShowtime.gioBatDau).slice(0, 5)} - {selectedDate}
              </strong>
            </div>
            <div className="summary-row">
              <span>Ghế chọn ({selectedSeats.length})</span>
              <div className="selected-seats-badge-list">
                {selectedSeats.length === 0 ? (
                  <em className="text-muted">Chưa chọn ghế nào</em>
                ) : (
                  selectedSeats.map((s) => (
                    <span key={s.maGhe} className="seat-badge">
                      {getSeatLabel(s)} ({s.loaiGhe === "DOI" ? "Đôi" : s.loaiGhe === "VIP" ? "VIP" : "Thường"})
                    </span>
                  ))
                )}
              </div>
            </div>
            <div className="summary-divider" />
            <div className="summary-total-row">
              <span>Tiền vé</span>
              <strong className="summary-price">{money(ticketTotal)}</strong>
            </div>
          </div>

          <button
            type="button"
            className="counter-btn-submit-order"
            disabled={selectedSeats.length === 0}
            onClick={() => setStep(4)}
          >
            TIẾP TỤC: CHỌN COMBO BỎNG NƯỚC →
          </button>
        </aside>
      </div>
    </section>
  );
}
