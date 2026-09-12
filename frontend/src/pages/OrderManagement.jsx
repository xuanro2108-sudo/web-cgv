import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Html5Qrcode } from "html5-qrcode";
import "./OrderManagement.css";

const api = (import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api").replace(/\/$/, "");
const labels = {
  DA_THANH_TOAN: "Đã đặt",
  DA_SU_DUNG: "Đã sử dụng",
  DA_HUY: "Đã hủy",
  CHO_THANH_TOAN: "Chờ thanh toán",
};
const money = (value) => new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(value);
const showtimeDate = (value) => value ? new Intl.DateTimeFormat("vi-VN", { day: "2-digit", month: "2-digit", year: "numeric" }).format(new Date(value)) : "—";
const showtimeTime = (value) => value ? String(value).slice(0, 5) : "—";
const date = (value) => value ? new Intl.DateTimeFormat("vi-VN", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", hourCycle: "h23" }).format(new Date(value)).replace(", ", " ") : "—";

async function request(path, options = {}) {
  const response = await fetch(api + path, {
    ...options,
    headers: {
      Accept: "application/json",
      "Content-Type": "application/json",
      Authorization: "Bearer " + (localStorage.getItem("token") || ""),
    },
  });
  const body = await response.json().catch(() => null);
  if (response.status === 401) {
    throw new Error("Phiên đăng nhập không hợp lệ. Hãy đăng xuất và đăng nhập lại.");
  }
  if (!response.ok) throw new Error(body?.message || "Không thể thực hiện yêu cầu.");
  return body;
}

export default function OrderManagement() {
  const navigate = useNavigate();
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("DA_THANH_TOAN");
  const [page, setPage] = useState(1);
  const [result, setResult] = useState(null);
  const [cameraOpen, setCameraOpen] = useState(false);
  const [cameraError, setCameraError] = useState("");
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [detailState, setDetailState] = useState({ loading: false, error: "" });
  const [confirmState, setConfirmState] = useState({ loading: false, error: "" });
  const [printOrder, setPrintOrder] = useState(null);
  const [printState, setPrintState] = useState({ awaitingConfirmation: false });
  const [reload, setReload] = useState(0);
  const qrReaderRef = useRef(null);
  useEffect(() => {
    const controller = new AbortController();
    const timer = setTimeout(() => {
      request(`/quan-ly/don-hangs?q=${encodeURIComponent(query.trim())}&trangThai=${status}&page=${page}`, { signal: controller.signal })
        .then((data) => setResult({ data }))
        .catch((error) => {
          if (error.name !== "AbortError") setResult({ error: error.message });
        });
    }, 200);
    return () => { clearTimeout(timer); controller.abort(); };
  }, [query, status, page, reload]);

  function stopCamera() {
    const reader = qrReaderRef.current;
    qrReaderRef.current = null;
    if (!reader) return;
    const cleanup = () => reader.clear().catch(() => {});
    if (reader.isScanning) {
      reader.stop().catch(() => {}).finally(cleanup);
      return;
    }
    cleanup();
  }

  function openCamera() {
    setCameraError("");
    setCameraOpen(true);
  }

  useEffect(() => {
    if (!cameraOpen) return undefined;
    let active = true;
    const reader = new Html5Qrcode("om-qr-reader");
    qrReaderRef.current = reader;
    reader.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: { width: 240, height: 240 } },
      (decodedText) => {
        if (!active) return;
        stopCamera();
        setCameraOpen(false);
        previewQr(decodedText);
      },
      () => {},
    ).catch((error) => {
      if (!active) return;
      stopCamera();
      setCameraOpen(false);
      setCameraError(error?.name === "NotAllowedError" ? "Bạn chưa cấp quyền dùng camera. Hãy cho phép camera rồi thử lại." : "Không thể mở camera. Kiểm tra quyền truy cập hoặc camera có đang được ứng dụng khác sử dụng không.");
    });
    return () => {
      active = false;
      stopCamera();
    };
  }, [cameraOpen]);

  async function previewQr(value) {
    if (!value.trim()) return;
    setSelectedOrder(null);
    setDetailState({ loading: true, error: "" });
    try {
      const response = await request("/quan-ly/don-hangs/xem-qr", {
        method: "POST",
        body: JSON.stringify({ qrData: value.trim() }),
      });
      setSelectedOrder(response.data);
      setDetailState({ loading: false, error: "" });
    } catch (error) {
      setDetailState({ loading: false, error: error.message });
    }
  }

  function printTickets() {
    if (!selectedOrder) return;
    setPrintOrder(selectedOrder);
    window.setTimeout(() => {
      window.print();
      setPrintState({ awaitingConfirmation: true });
    }, 100);
  }

  async function confirmPrinted() {
    if (!selectedOrder) return;
    setConfirmState({ loading: true, error: "" });
    try {
      const response = await request(`/quan-ly/don-hangs/${encodeURIComponent(selectedOrder.maDonHang)}/quet-ma`, {
        method: "POST",
        body: JSON.stringify({}),
      });
      setSelectedOrder(response.data);
      setConfirmState({ loading: false, error: "" });
      setPrintState({ awaitingConfirmation: false });
      setReload((value) => value + 1);
    } catch (error) {
      setConfirmState({ loading: false, error: error.message });
    }
  }

  async function showOrder(maDonHang) {
    setSelectedOrder(null);
    setDetailState({ loading: true, error: "" });
    try {
      const response = await request(`/quan-ly/don-hangs/${encodeURIComponent(maDonHang)}`);
      setSelectedOrder(response.data);
    } catch (error) {
      setDetailState({ loading: false, error: error.message });
      return;
    }
    setDetailState({ loading: false, error: "" });
  }

  const data = result?.data;
  return (
    <section className="om-page">
      <p className="om-breadcrumb">Hệ thống quản lý / Đơn hàng & vé</p>
      <header className="om-heading">
        <div><h1>Quản lý đơn hàng & vé</h1><p>Kiểm tra đơn đã đặt và xác nhận vé khi khách đến rạp.</p></div>
        <div className="om-heading-actions"><button type="button" className="om-counter-trigger" onClick={() => navigate("/dashboard/ban-ve-tai-quay")}>Bán vé tại quầy</button><button type="button" className="om-qr-trigger" onClick={openCamera} aria-label="Quét mã QR"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V5h4M15 5h5v4M20 15v4h-4M9 20H4v-5M7 7h3v3H7zM14 7h3v3h-3zM7 14h3v3H7zM14 14h3v3h-3z" /></svg><span>Quét QR</span></button></div>
      </header>
      {cameraError && <p className="om-qr-error" role="alert">{cameraError}</p>}

      <section className="om-card">
        <div className="om-filters">
          <label className="om-search">Tìm đơn hàng<input value={query} onChange={(event) => { setQuery(event.target.value); setPage(1); }} placeholder="Mã đơn hoặc tên khách hàng…" /></label>
          <label>Trạng thái<select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}><option value="">Tất cả</option><option value="DA_THANH_TOAN">Đã đặt</option><option value="DA_SU_DUNG">Đã sử dụng</option><option value="DA_HUY">Đã hủy</option><option value="CHO_THANH_TOAN">Chờ thanh toán</option></select></label>
        </div>
        {result?.error && <div className="om-error" role="alert">{result.error} <button type="button" onClick={() => setReload((value) => value + 1)}>Tải lại</button></div>}
        {!result && <p className="om-empty">Đang tải đơn hàng…</p>}
        {data && <div className="om-table-wrap"><table><thead><tr><th>Đơn hàng</th><th>Khách hàng</th><th>Phim & ghế</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead><tbody>
          {data.data.map((order) => <tr key={order.maDonHang} className="om-order-row" onClick={() => showOrder(order.maDonHang)}>
            <td><strong>{order.maDonHang}</strong><small>{order.kieuDat}</small></td>
            <td>{order.khach_hang?.hoTen || "—"}<small>{order.khach_hang?.email || ""}</small></td>
            <td>{order.ve_ghes?.map((ticket) => `${ticket.lich_chieu?.phim?.tenPhim || "Vé"} · ${ticket.ghe?.hang || ""}${ticket.ghe?.cot || ""}`).join(", ") || "Không có vé"}</td>
            <td>{date(order.ngayDat)}</td><td>{money(order.tongTien)}</td>
            <td><span className={`om-badge ${order.trangThai === "DA_THANH_TOAN" ? "paid" : order.trangThai === "DA_SU_DUNG" ? "used" : ""}`}>{labels[order.trangThai] || order.trangThai}</span></td>
          </tr>)}
        </tbody></table>{!data.data.length && <p className="om-empty">Không có đơn hàng phù hợp.</p>}
        <footer className="om-pagination"><span>{data.total} đơn hàng</span><div><button disabled={page <= 1} onClick={() => setPage(page - 1)}>Trước</button><span>Trang {data.current_page} / {data.last_page}</span><button disabled={page >= data.last_page} onClick={() => setPage(page + 1)}>Sau</button></div></footer></div>}
      </section>
      {(selectedOrder || detailState.loading || detailState.error) && <div className="om-modal-backdrop" role="presentation" onMouseDown={() => { setSelectedOrder(null); setDetailState({ loading: false, error: "" }); }}>
        <section className="om-detail-modal" role="dialog" aria-modal="true" aria-labelledby="om-detail-title" onMouseDown={(event) => event.stopPropagation()}>
          <button className="om-close" type="button" aria-label="Đóng chi tiết đơn hàng" onClick={() => { setSelectedOrder(null); setDetailState({ loading: false, error: "" }); }}>×</button>
          {detailState.loading && <p className="om-empty">Đang tải chi tiết đơn hàng…</p>}
          {detailState.error && <div className="om-error" role="alert">{detailState.error}</div>}
          {selectedOrder && <OrderDetail order={selectedOrder} confirmState={confirmState} printState={printState} onPrint={printTickets} onConfirmPrinted={confirmPrinted} onCancelPrint={() => setPrintState({ awaitingConfirmation: false })} />}
        </section>
      </div>}
      {cameraOpen && <div className="om-modal-backdrop" role="presentation" onMouseDown={() => { stopCamera(); setCameraOpen(false); }}>
        <section className="om-camera-modal" role="dialog" aria-modal="true" aria-labelledby="om-camera-title" onMouseDown={(event) => event.stopPropagation()}>
          <button className="om-close" type="button" aria-label="Đóng camera" onClick={() => { stopCamera(); setCameraOpen(false); }}>×</button>
          <h2 id="om-camera-title">Đưa mã QR vào khung hình</h2>
          <p>Camera sẽ tự nhận diện mã QR của vé.</p>
          <div id="om-qr-reader" className="om-qr-reader" />
        </section>
      </div>}
      {printOrder && <PrintDocuments order={printOrder} />}
    </section>
  );
}

function OrderDetail({ order, confirmState, printState, onPrint, onConfirmPrinted, onCancelPrint }) {
  const tickets = order.ve_ghes || [];
  const combos = order.chi_tiet_combo_don_hangs || [];
  const comboQuantity = combos.reduce((total, line) => total + Number(line.soLuong || 0), 0);
  return <>
    <header className="om-detail-heading"><div><p>CHI TIẾT ĐƠN HÀNG</p><h2 id="om-detail-title">{order.maDonHang}</h2></div><span className={`om-badge ${order.trangThai === "DA_THANH_TOAN" ? "paid" : order.trangThai === "DA_SU_DUNG" ? "used" : ""}`}>{labels[order.trangThai] || order.trangThai}</span></header>
    <div className="om-detail-meta"><span>Khách hàng: <strong>{order.khach_hang?.hoTen || "—"}</strong></span><span>Ngày đặt: <strong>{date(order.ngayDat)}</strong></span></div>
    <h3>Vé & ghế ({tickets.length})</h3>
    {tickets.length ? <div className="om-detail-list">{tickets.map((ticket) => <div className="om-detail-item" key={ticket.maVe}><div><strong>{ticket.lich_chieu?.phim?.tenPhim || "Vé xem phim"}</strong><small>Ghế {ticket.ghe?.hang || ""}{ticket.ghe?.cot || ""} · {ticket.lich_chieu?.phong_chieu?.tenPhong || "—"}</small><small>{showtimeDate(ticket.lich_chieu?.ngayChieu)} · {showtimeTime(ticket.lich_chieu?.gioBatDau)}</small></div><strong>{money(ticket.giaVe)}</strong></div>)}</div> : <p className="om-detail-empty">Đơn hàng không có vé.</p>}
    <h3>Combo bắp nước ({comboQuantity})</h3>
    {combos.length ? <div className="om-detail-list">{combos.map((line) => <div className="om-detail-item" key={line.maChiTiet}><div><strong>{line.combo?.tenCombo || "Combo"}</strong><small>{line.soLuong} × {money(line.donGia)}</small></div><strong>{money(line.thanhTien)}</strong></div>)}</div> : <p className="om-detail-empty">Không có combo bắp nước.</p>}
    <footer className="om-detail-total"><span>Tổng thanh toán</span><strong>{money(order.tongTien)}</strong></footer>
    {order.trangThai === "DA_THANH_TOAN" && <div className="om-confirm-area">
      {confirmState.error && <p className="om-confirm-error" role="alert">{confirmState.error}</p>}
      {!printState.awaitingConfirmation && <button type="button" onClick={onPrint}>In vé</button>}
      {printState.awaitingConfirmation && <div className="om-print-result"><p>Bạn đã bấm <strong>In</strong> trong hộp thoại in chưa?</p><button type="button" onClick={onConfirmPrinted} disabled={confirmState.loading}>{confirmState.loading ? "Đang xác nhận…" : "Đã in xong"}</button><button type="button" className="om-cancel-print" onClick={onCancelPrint} disabled={confirmState.loading}>Hủy</button></div>}
    </div>}
  </>;
}

function PrintDocuments({ order }) {
  const tickets = order.ve_ghes || [];
  const combos = order.chi_tiet_combo_don_hangs || [];
  return <div className="om-print-area">
    {tickets.map((ticket) => <article className="om-print-ticket" key={ticket.maVe}>
      <header className="om-ticket-header"><span>CGV CINEMAS</span><h1>VÉ XEM PHIM</h1><small>Khách hàng: {order.khach_hang?.hoTen || "Khách hàng"}</small></header>
      <div className="om-ticket-perforation" />
      <section className="om-ticket-body">
        <p className="om-ticket-cinema">CGV · {ticket.lich_chieu?.phong_chieu?.tenPhong || "Phòng chiếu"}</p>
        <h2>{ticket.lich_chieu?.phim?.tenPhim || "Vé xem phim"}</h2>
        <p className="om-ticket-showtime">{showtimeDate(ticket.lich_chieu?.ngayChieu)} · {showtimeTime(ticket.lich_chieu?.gioBatDau)}</p>
        <div className="om-ticket-seat"><span>GHẾ</span><strong>{ticket.ghe?.hang || ""}{ticket.ghe?.cot || ""}</strong><span>{ticket.ghe?.loaiGhe || "THƯỜNG"}</span></div>
        <p className="om-ticket-price">{money(ticket.giaVe)}</p>
      </section>
      <div className="om-ticket-perforation" />
      <footer className="om-ticket-footer"><p>Mã đơn: {order.maDonHang}</p><div className="om-ticket-barcode" aria-label={ticket.maVe}><span>{ticket.maVe}</span></div><small>Vui lòng xuất trình vé này tại rạp</small></footer>
    </article>)}
    {combos.map((line) => <article className="om-print-combo" key={line.maChiTiet}>
      <header className="om-ticket-header"><span>CGV CINEMAS</span><h1>PHIẾU NHẬN COMBO</h1><small>Mã đơn: {order.maDonHang}</small></header>
      <div className="om-ticket-perforation" />
      <section className="om-ticket-body"><h2>{line.combo?.tenCombo || "Combo bắp nước"}</h2><div className="om-ticket-seat"><span>SỐ LƯỢNG</span><strong>{line.soLuong}</strong><span>PHẦN</span></div><p className="om-ticket-price">{money(line.thanhTien)}</p></section>
      <div className="om-ticket-perforation" />
      <footer className="om-ticket-footer"><div className="om-ticket-barcode" aria-label={line.maChiTiet}><span>{line.maChiTiet}</span></div><small>Đổi combo tại quầy bắp nước</small></footer>
    </article>)}
  </div>;
}
