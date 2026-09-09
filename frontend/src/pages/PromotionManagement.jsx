import { useEffect, useRef, useState } from "react";
import "./CustomerManagement.css";
import "./PromotionManagement.css";

const api = (import.meta.env.VITE_API_URL || "http://127.0.0.1:8000/api").replace(/\/$/, "");
const imageUrl = (path) => path ? api.replace(/\/api$/, "") + '/storage/' + path : null;
const money = (value) => new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(value);
const day = (value) => value?.slice(0, 10) || "";
const date = (value) => day(value).split("-").reverse().join("/");
const empty = { maKM: "", tenKM: "", hinhThuc: "GIAM_PHAN_TRAM", giaTri: "", donToiThieu: "0", ngayBatDau: "", ngayKetThuc: "", trangThai: "HOAT_DONG" };

async function request(path, options = {}) {
  const response = await fetch(api + path, { ...options, headers: { Accept: "application/json", ...(!(options.body instanceof FormData) && { "Content-Type": "application/json" }), Authorization: `Bearer ${localStorage.getItem("token") || ""}` } });
  const body = await response.json().catch(() => null);
  if (!response.ok) {
    const error = new Error(response.status === 401 ? "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại." : response.status === 403 ? "Bạn không có quyền quản lý khuyến mại." : body?.message || "Không thể thực hiện yêu cầu.");
    error.fields = body?.errors;
    throw error;
  }
  if (!body) throw new Error("Dữ liệu phản hồi không hợp lệ.");
  return body;
}

function PromotionDialog({ mode, item, onClose, onSaved }) {
  const dialog = useRef(null);
  const [form, setForm] = useState(() => item ? Object.fromEntries(Object.keys(empty).map((key) => [key, key.startsWith("ngay") ? day(item[key]) : String(item[key])])) : empty);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [fields, setFields] = useState({});
  const [photo, setPhoto] = useState(null);
  const [removePhoto, setRemovePhoto] = useState(false);
  const [preview, setPreview] = useState(null);
  useEffect(() => {
    return () => { if (preview) URL.revokeObjectURL(preview); };
  }, [preview]);
  const viewing = mode === "view";
  const used = Number(item?.don_hangs_count) > 0;
  useEffect(() => {
    const previous = document.activeElement;
    dialog.current.showModal();
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => { document.body.style.overflow = overflow; previous?.focus(); };
  }, []);
  const close = () => { if (!busy) onClose(); };
  function change(event) { setForm({ ...form, [event.target.name]: event.target.value }); setFields({ ...fields, [event.target.name]: undefined }); }
  async function submit(event) {
    event.preventDefault();
    if (busy) return;
    setBusy(true); setError(""); setFields({});
    try {
      const payload = mode === "create" ? form : Object.fromEntries(Object.entries(form).filter(([key, value]) => key !== "maKM" && value !== (key.startsWith("ngay") ? day(item[key]) : String(item[key]))));
      if (mode === "edit" && !Object.keys(payload).length && !photo && !removePhoto) { setError("Chưa có thay đổi để lưu."); return; }
      const body = new FormData();
      Object.entries(payload).forEach(([key, value]) => body.append(key, value));
      if (photo) body.append('anh', photo);
      if (removePhoto) body.append('xoaAnh', '1');
      if (mode === "edit") body.append('_method', 'PATCH');
      await request('/khuyen-mais' + (item ? '/' + encodeURIComponent(item.maKM) : ''), { method: mode === "delete" ? "DELETE" : "POST", ...(mode !== "delete" && { body }) });
      onSaved(mode === "delete" ? "Đã ngừng áp dụng khuyến mại. Lịch sử đơn hàng được giữ lại." : "Đã lưu khuyến mại.");
    } catch (error) { setError(error.message); setFields(error.fields || {}); }
    finally { setBusy(false); }
  }
  const field = (name, title, type = "text", extra = {}) => <label>{title}<input name={name} type={type} value={form[name]} onChange={change} required disabled={viewing || busy || (name === "maKM" && mode === "edit") || (used && !["tenKM", "trangThai"].includes(name))} {...extra} aria-invalid={!!fields[name]} />{fields[name] && <small className="pm-field-error">{fields[name].join(" ")}</small>}</label>;
  return <dialog ref={dialog} className="cm-dialog pm-dialog" aria-labelledby="pm-title" onCancel={(event) => { event.preventDefault(); close(); }}>
    <div className="cm-dialog-content"><header className="cm-heading"><h2 id="pm-title">{{ create: "Thêm khuyến mại", edit: "Sửa khuyến mại", view: "Thông tin khuyến mại", delete: "Xóa khuyến mại?" }[mode]}</h2><button type="button" disabled={busy} onClick={close} aria-label="Đóng">✕</button></header>
      <form onSubmit={submit}>
        {mode === "delete" ? <><p>Bạn muốn xóa khuyến mại <strong>{item.maKM} — {item.tenKM}</strong>?</p><p className="cm-demo">Mã sẽ chuyển sang Ngừng hoạt động và không thể áp dụng cho đơn hàng mới. Thông tin khuyến mại và lịch sử đơn hàng vẫn được giữ lại.</p></> : <>
          {used && <p className="cm-demo">Mã đã liên kết với {item.don_hangs_count} đơn hàng. Chỉ có thể sửa tên và trạng thái; hãy tạo mã mới nếu cần đổi điều kiện giảm.</p>}
          <div className="pm-form">
            <div className="pm-photo">
              <label>Ảnh khuyến mại
                {!viewing && <input type="file" accept="image/jpeg,image/png,image/webp" disabled={busy} onChange={(event) => {
                  const file = event.target.files?.[0];
                  if (!file) return;
                  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 2 * 1024 * 1024) { setFields({ ...fields, anh: ['Chọn ảnh JPG, PNG hoặc WebP tối đa 2 MB.'] }); event.target.value = ''; return; }
                  setPreview(URL.createObjectURL(file)); setPhoto(file); setRemovePhoto(false); setFields({ ...fields, anh: undefined });
                }} />}
              </label>
              {(photo ? preview : !removePhoto && imageUrl(item?.hinhAnh)) ? <img className="pm-preview" src={photo ? preview : imageUrl(item?.hinhAnh)} alt="Ảnh khuyến mại" /> : <p>Chưa có ảnh</p>}
              {!viewing && <><small>JPG, PNG hoặc WebP, tối đa 2 MB.</small>{(photo || item?.hinhAnh) && <button type="button" disabled={busy} onClick={() => { setPhoto(null); setRemovePhoto(true); }}>Gỡ ảnh</button>}</>}
              {fields.anh && <small className="pm-field-error">{fields.anh.join(' ')}</small>}
            </div>
            {field("maKM", "Mã khuyến mại", "text", { maxLength: 50, pattern: "[A-Z0-9_\\-]+", title: "Chỉ dùng chữ in hoa, số, dấu gạch ngang hoặc gạch dưới" })}
            {field("tenKM", "Tên khuyến mại", "text", { maxLength: 255 })}
            <label>Hình thức<select name="hinhThuc" value={form.hinhThuc} onChange={change} disabled={viewing || busy || used}><option value="GIAM_PHAN_TRAM">Giảm phần trăm</option><option value="GIAM_GIA">Giảm số tiền</option></select></label>
            {field("giaTri", form.hinhThuc === "GIAM_PHAN_TRAM" ? "Giá trị giảm (%)" : "Giá trị giảm (đ)", "number", { min: 0.01, max: form.hinhThuc === "GIAM_PHAN_TRAM" ? 100 : 99999999.99, step: "0.01" })}
            {field("donToiThieu", "Đơn tối thiểu (đ)", "number", { min: 0, max: 99999999.99, step: "0.01" })}
            <label>Trạng thái<select name="trangThai" value={form.trangThai} onChange={change} disabled={viewing || busy}><option value="HOAT_DONG">Hoạt động</option><option value="NGUNG_HOAT_DONG">Ngừng hoạt động</option></select></label>
            {field("ngayBatDau", "Ngày bắt đầu", "date")}{field("ngayKetThuc", "Ngày kết thúc", "date", { min: form.ngayBatDau })}
          </div>
        </>}
        {error && <p className="cm-error" role="alert">{error}</p>}
        <footer className="cm-modal-actions"><button type="button" disabled={busy} onClick={close}>{viewing ? "Đóng" : "Hủy"}</button>{!viewing && <button type="submit" className="cm-delete-button" disabled={busy}>{busy ? "Đang xử lý…" : mode === "delete" ? "Xác nhận xóa" : "Lưu khuyến mại"}</button>}</footer>
      </form>
    </div>
  </dialog>;
}

export default function PromotionManagement() {
  const [q, setQ] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const [result, setResult] = useState(null);
  const [modal, setModal] = useState(null);
  const [notice, setNotice] = useState("");
  const params = new URLSearchParams({ q: q.trim(), trangThai: status, page }).toString();
  const key = `${params}:${revision}`;
  useEffect(() => {
    let active = true;
    const controller = new AbortController();
    const timer = setTimeout(() => {
      request('/quan-ly/khuyen-mais?' + params, { signal: controller.signal })
        .then((data) => { if (active) setResult({ key, data }); })
        .catch((error) => { if (active) setResult({ key, error: error.message }); });
    }, 250);
    return () => { active = false; clearTimeout(timer); controller.abort(); };
  }, [key, params]);
  const loading = result?.key !== key;
  const data = loading ? null : result.data;
  const error = loading ? null : result.error;
  const now = new Date();
  const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;
  const period = (item) => item.trangThai !== "HOAT_DONG" ? "Ngừng hoạt động" : day(item.ngayBatDau) > today ? "Chưa bắt đầu" : day(item.ngayKetThuc) < today ? "Hết hạn" : "Đang áp dụng";
  return <section className="cm-page">
    <p className="cm-breadcrumb">Hệ thống quản lý / Khuyến mại</p>
    <header className="cm-heading"><div><h1>Quản lý khuyến mại</h1><p>Theo dõi ưu đãi và quản lý các chương trình giảm giá.</p></div><button type="button" onClick={() => setModal({ mode: "create" })}>+ Thêm khuyến mại</button></header>
    {notice && <div className="cm-notice" role="status">{notice}<button onClick={() => setNotice("")} aria-label="Đóng thông báo">✕</button></div>}
    <section className="cm-card"><div className="cm-card-heading"><h2>Danh sách khuyến mại {data && <span>{data.total}</span>}</h2></div>
      <div className="cm-filters"><label className="cm-search">Tra cứu khuyến mại<input type="search" maxLength={255} placeholder="Nhập mã hoặc tên khuyến mại…" value={q} onChange={(event) => { setQ(event.target.value); setPage(1); }} /></label><label>Trạng thái<select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}><option value="">Tất cả</option><option value="HOAT_DONG">Hoạt động</option><option value="NGUNG_HOAT_DONG">Ngừng hoạt động</option></select></label><button onClick={() => { setQ(""); setStatus(""); setPage(1); }}>Xóa bộ lọc</button></div>
      {loading && <p className="cm-empty" role="status">Đang tải khuyến mại…</p>}
      {error && <div className="cm-error" role="alert">{error} <button onClick={() => setRevision(revision + 1)}>Thử lại</button></div>}
      {data && <><div className="cm-table-wrap"><table><thead><tr><th>Khuyến mại</th><th>Mức giảm</th><th>Đơn tối thiểu</th><th>Thời gian</th><th>Tình trạng</th><th>Thao tác</th></tr></thead><tbody>{data.data.map((item) => <tr key={item.maKM}>
        <td>{item.hinhAnh && <img className="pm-thumbnail" src={imageUrl(item.hinhAnh)} alt={item.tenKM} loading="lazy" />}<strong>{item.tenKM}</strong><small>{item.maKM}</small></td><td>{item.hinhThuc === "GIAM_PHAN_TRAM" ? `${Number(item.giaTri)}%` : money(item.giaTri)}</td><td>{money(item.donToiThieu)}</td><td>{date(item.ngayBatDau)}<small>đến {date(item.ngayKetThuc)}</small></td><td><span className={`cm-badge ${period(item) === "Đang áp dụng" ? "cm-green" : "cm-gray"}`}>{period(item)}</span></td>
        <td><div className="cm-actions"><button onClick={() => setModal({ mode: "view", item })} aria-label={`Xem ${item.maKM}`}>Chi tiết</button><button onClick={() => setModal({ mode: "edit", item })} aria-label={`Sửa ${item.maKM}`}>Sửa</button><button className="cm-danger" disabled={item.trangThai === "NGUNG_HOAT_DONG"} onClick={() => setModal({ mode: "delete", item })} aria-label={`Xóa ${item.maKM}`}>Xóa</button></div></td>
      </tr>)}</tbody></table></div>{!data.data.length && <p className="cm-empty">Không tìm thấy khuyến mại phù hợp.</p>}
      <footer className="cm-pagination"><span>{data.total} khuyến mại</span><div><button disabled={page <= 1} onClick={() => setPage(page - 1)}>Trước</button><span>Trang {page} / {data.last_page}</span><button disabled={page >= data.last_page} onClick={() => setPage(page + 1)}>Sau</button></div></footer></>}
    </section>
    {modal && <PromotionDialog {...modal} onClose={() => setModal(null)} onSaved={(message) => { setModal(null); setNotice(message); setPage(1); setRevision(revision + 1); }} />}
  </section>;
}
