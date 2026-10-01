export function showtimeStatus(showtime, now = Date.now()) {
  if (showtime.trangThai !== "HOAT_DONG") {
    return { label: "Đã hủy", className: "cancelled" };
  }
  const date = showtime.ngayChieu?.slice(0, 10);
  const start = Date.parse(`${date}T${showtime.gioBatDau}+07:00`);
  let end = Date.parse(`${date}T${showtime.gioKetThuc}+07:00`);
  if (!Number.isFinite(start) || !Number.isFinite(end)) {
    return { label: "Chưa đủ thời gian", className: "unknown" };
  }
  if (end < start) end += 24 * 60 * 60 * 1000;
  if (now < start) return { label: "Sắp chiếu", className: "upcoming" };
  if (now < end) return { label: "Đang chiếu", className: "active" };
  return { label: "Đã chiếu", className: "finished" };
}
