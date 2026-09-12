import { formatShowtimeDate } from "../../../utils/counterSaleUtils";
import {
    money
} from "../../../utils/counterSaleUtils";

export default function ShowtimeStep({
  selectedMovie,
  setStep,
  availableDates,
  selectedDate,
  setSelectedDate,
  dateShowtimes,
  loadingShowtime,
  handleSelectShowtime,
}) {
  return (
    <section className="counter-step-view">
      <div className="counter-selected-movie-bar">
        {selectedMovie.hinhAnh && (
          <img
            src={selectedMovie.hinhAnh}
            alt={selectedMovie.tenPhim}
            className="summary-poster"
          />
        )}
        <div className="summary-details">
          <span className="summary-tag">PHIM ĐANG CHỌN</span>
          <h2>{selectedMovie.tenPhim}</h2>
          <p>
            {selectedMovie.theLoai || "Đang cập nhật"} ·{" "}
            {selectedMovie.thoiLuong ? `${selectedMovie.thoiLuong} phút` : ""}
          </p>
        </div>
        <button
          type="button"
          className="counter-btn-change"
          onClick={() => setStep(1)}
        >
          ← Đổi phim khác
        </button>
      </div>

      <div className="counter-date-tabs">
        {availableDates.length === 0 ? (
          <p className="counter-no-dates">Phim này hiện chưa có lịch chiếu khả dụng.</p>
        ) : (
          availableDates.map((date) => {
            const f = formatShowtimeDate(date);
            const isSelected = selectedDate === date;

            return (
              <button
                type="button"
                key={date}
                className={`date-tab-button ${isSelected ? "active" : ""}`}
                onClick={() => setSelectedDate(date)}
              >
                <strong>{f.day}</strong>
                <span>/{f.month}</span>
                <small>- {f.weekday}</small>
              </button>
            );
          })
        )}
      </div>

      <div className="counter-showtimes-panel">
        <h3>
          Danh sách suất chiếu ngày{" "}
          {selectedDate
            ? formatShowtimeDate(selectedDate).day + "/" + formatShowtimeDate(selectedDate).month
            : ""}
        </h3>

        {dateShowtimes.length === 0 ? (
          <p className="counter-empty-message">Không có suất chiếu nào trong ngày này.</p>
        ) : (
          <div className="counter-showtimes-grid">
            {dateShowtimes.map((item) => {
              const roomName =
                item.phong_chieu?.tenPhong ||
                item.phongChieu?.tenPhong ||
                "Phòng chiếu";

              return (
                <div
                  className="counter-showtime-card"
                  key={item.maLichChieu}
                  onClick={() => handleSelectShowtime(item)}
                >
                  <div className="showtime-time-col">
                    <strong>{String(item.gioBatDau).slice(0, 5)}</strong>
                    <span>~ {String(item.gioKetThuc).slice(0, 5)}</span>
                  </div>
                  <div className="showtime-info-col">
                    <h4>{roomName}</h4>
                    <span className="badge-available">{item.gheTrong ?? 0} ghế trống</span>
                    <p className="showtime-price-note">
                      Giá vé từ {money(item.giaVeCoBan)}
                    </p>
                  </div>
                  <button
                    type="button"
                    className="counter-btn-pick-showtime"
                    disabled={loadingShowtime}
                    onClick={(e) => {
                      e.stopPropagation();
                      handleSelectShowtime(item);
                    }}
                  >
                    {loadingShowtime ? "Đang tải..." : "Chọn chỗ →"}
                  </button>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </section>
  );
}
