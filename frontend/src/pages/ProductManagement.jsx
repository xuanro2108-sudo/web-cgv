import { useEffect, useRef, useState } from "react";
import { getProducts, saveProduct, stopProduct } from "../services/productManagement";
import "./ComboManagement.css";

const types = { BAP: "Bắp", NUOC: "Nước", DO_AN: "Đồ ăn" };
const statuses = { HOAT_DONG: "Đang bán", NGUNG_HOAT_DONG: "Ngừng bán" };
const emptyProduct = { maSP: "", tenSP: "", loaiSP: "BAP", donGia: "", moTa: "", hinhAnh: "", trangThai: "HOAT_DONG" };
const money = (value) => new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(Number(value));

function ProductImage({ src, title }) {
  const [failed, setFailed] = useState(false);
  return src && !failed
    ? <img className="combo-admin-image" src={src} alt={title} loading="lazy" onError={() => setFailed(true)} />
    : <div className="combo-admin-image empty">{src ? "Không tải được ảnh" : "Chưa có ảnh"}</div>;
}

export default function ProductManagement() {
  const [result, setResult] = useState({ data: [], total: 0, last_page: 1 });
  const [query, setQuery] = useState({ keyword: "", type: "", status: "", page: 1 });
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [revision, setRevision] = useState(0);
  const [form, setForm] = useState(null);
  const [editing, setEditing] = useState(null);
  const [formError, setFormError] = useState("");
  const [image, setImage] = useState(null);
  const [preview, setPreview] = useState("");
  const [busy, setBusy] = useState(false);
  const dialog = useRef(null);

  useEffect(() => {
    const controller = new AbortController();
    getProducts(query, controller.signal).then((data) => {
      if (!controller.signal.aborted) setResult(data);
    }).catch((err) => { if (!controller.signal.aborted) setError(err.message); })
      .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [query, revision]);
  useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);

  const changeQuery = (next) => { setLoading(true); setError(""); setQuery(next); };
  const refresh = () => { setLoading(true); setError(""); setRevision((value) => value + 1); };
  const open = (product) => {
    setEditing(product?.maSP || null);
    setForm(product ? Object.fromEntries(Object.keys(emptyProduct).map((key) => [key, product[key] ?? ""])) : { ...emptyProduct });
    setFormError(""); setImage(null); setPreview(""); setMessage("");
    dialog.current.showModal();
  };
  const close = () => { dialog.current.close(); setForm(null); setImage(null); setPreview(""); };
  const change = (name, value) => { setForm({ ...form, [name]: value }); setFormError(""); };
  const chooseImage = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 2 * 1024 * 1024) {
      setFormError("Chọn ảnh JPG, PNG hoặc WebP không quá 2 MB."); event.target.value = ""; return;
    }
    setImage(file); setPreview(URL.createObjectURL(file)); setFormError("");
  };
  const submit = async (event) => {
    event.preventDefault();
    if (busy) return;
    setFormError("");
    if (!form.maSP.trim()) { setFormError("Vui lòng nhập mã sản phẩm."); return; }
    if (!form.tenSP.trim()) { setFormError("Vui lòng nhập tên sản phẩm."); return; }
    setBusy(true);
    try {
      await saveProduct(editing, { ...form, maSP: form.maSP.trim(), tenSP: form.tenSP.trim(), moTa: form.moTa.trim() }, image);
      close(); setMessage(editing ? "Cập nhật sản phẩm thành công." : "Thêm sản phẩm thành công."); refresh();
    } catch (err) { setFormError(err.message); }
    finally { setBusy(false); }
  };
  const stop = async (product) => {
    if (busy || !window.confirm(`Ngừng bán sản phẩm “${product.tenSP}”? Dữ liệu trong các combo hiện có vẫn được giữ lại.`)) return;
    setBusy(true); setMessage(""); setError("");
    try { await stopProduct(product.maSP); setMessage("Đã ngừng bán sản phẩm."); refresh(); }
    catch (err) { setError(err.message); }
    finally { setBusy(false); }
  };

  return <section className="combo-admin">
    <header className="combo-admin-heading"><div><h1>Quản lý sản phẩm</h1><p>Quản lý bắp, nước và đồ ăn dùng trong các combo.</p></div><button disabled={busy} onClick={() => open()}>+ Thêm sản phẩm</button></header>
    <form className="combo-admin-filters" onSubmit={(event) => { event.preventDefault(); changeQuery({ ...query, keyword: search.trim(), page: 1 }); }}>
      <input aria-label="Tìm sản phẩm" placeholder="Tìm theo tên sản phẩm..." maxLength="100" value={search} onChange={(event) => setSearch(event.target.value)} />
      <select aria-label="Lọc loại sản phẩm" value={query.type} onChange={(event) => changeQuery({ ...query, type: event.target.value, page: 1 })}><option value="">Tất cả loại</option>{Object.entries(types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>
      <select aria-label="Lọc trạng thái" value={query.status} onChange={(event) => changeQuery({ ...query, status: event.target.value, page: 1 })}><option value="">Tất cả trạng thái</option>{Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>
      <button>Tìm kiếm</button><button type="button" className="secondary" disabled={loading || busy} onClick={refresh}>Tải lại</button>
    </form>
    {error && <p className="combo-admin-error" role="alert">{error}</p>}{message && <p className="combo-admin-success" role="status">{message}</p>}
    <div className="combo-admin-table"><table><thead><tr><th>Ảnh</th><th>Sản phẩm</th><th>Loại</th><th>Giá bán</th><th>Trạng thái</th><th>Thao tác</th></tr></thead><tbody>
      {loading ? <tr><td colSpan="6">Đang tải sản phẩm...</td></tr> : result.data.length === 0 ? <tr><td colSpan="6">Không có sản phẩm phù hợp.</td></tr> : result.data.map((product) => <tr key={product.maSP}>
        <td><ProductImage key={product.hinhAnh} src={product.hinhAnh} title={product.tenSP} /></td>
        <td><strong>{product.tenSP}</strong><small className="combo-admin-code">{product.maSP}</small><p className="combo-admin-description">{product.moTa}</p></td>
        <td>{types[product.loaiSP]}</td><td className="combo-admin-price">{money(product.donGia)}</td><td><span className={`combo-admin-status ${product.trangThai}`}>{statuses[product.trangThai]}</span></td>
        <td><div className="combo-admin-actions"><button className="secondary" disabled={busy} onClick={() => open(product)}>Sửa</button>{product.trangThai === "HOAT_DONG" && <button disabled={busy} onClick={() => stop(product)}>Ngừng bán</button>}</div></td>
      </tr>)}
    </tbody></table></div>
    <footer className="combo-admin-pagination"><span>{result.total} sản phẩm · Trang {query.page}/{result.last_page}</span><button disabled={loading || query.page <= 1} onClick={() => changeQuery({ ...query, page: query.page - 1 })}>Trước</button><button disabled={loading || query.page >= result.last_page} onClick={() => changeQuery({ ...query, page: query.page + 1 })}>Sau</button></footer>
    <dialog ref={dialog} className="combo-admin-dialog" aria-labelledby="product-form-title" onCancel={(event) => { event.preventDefault(); if (!busy) close(); }}>
      {form && <form onSubmit={submit}>
        <header className="combo-admin-heading"><h2 id="product-form-title">{editing ? "Sửa sản phẩm" : "Thêm sản phẩm"}</h2><button type="button" className="secondary" disabled={busy} aria-label="Đóng" onClick={close}>×</button></header>
        <div className="combo-admin-grid">
          <label>Mã sản phẩm *<input required maxLength="50" pattern="[A-Za-z0-9_-]+" title="Chữ cái không dấu, số, dấu gạch ngang và gạch dưới" placeholder="VD: SP001" value={form.maSP} disabled={busy || Boolean(editing)} onChange={(event) => change("maSP", event.target.value)} />{editing && <small>Mã sản phẩm được giữ cố định sau khi tạo.</small>}</label>
          <label>Tên sản phẩm *<input required maxLength="255" value={form.tenSP} disabled={busy} onChange={(event) => change("tenSP", event.target.value)} /></label>
          <label>Loại sản phẩm *<select required value={form.loaiSP} disabled={busy} onChange={(event) => change("loaiSP", event.target.value)}>{Object.entries(types).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
          <label>Giá bán (đồng) *<input type="number" required min="0" max="99999999.99" step="0.01" value={form.donGia} disabled={busy} onChange={(event) => change("donGia", event.target.value)} /></label>
          <label>Trạng thái<select value={form.trangThai} disabled={busy} onChange={(event) => change("trangThai", event.target.value)}>{Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
          <label className="full">Ảnh sản phẩm<input type="file" accept="image/jpeg,image/png,image/webp" disabled={busy} onChange={chooseImage} /><small>JPG, PNG hoặc WebP, tối đa 2 MB. Không chọn ảnh mới sẽ giữ ảnh hiện tại.</small></label>
          {(preview || form.hinhAnh) && <ProductImage key={preview || form.hinhAnh} src={preview || form.hinhAnh} title={form.tenSP || "Ảnh sản phẩm"} />}
          <label className="full">Mô tả<textarea rows="3" maxLength="5000" value={form.moTa} disabled={busy} onChange={(event) => change("moTa", event.target.value)} /></label>
        </div>
        {formError && <p className="combo-admin-error" role="alert">{formError}</p>}
        <footer className="combo-admin-actions"><button type="button" className="secondary" disabled={busy} onClick={close}>Hủy</button><button disabled={busy}>{busy ? "Đang lưu..." : editing ? "Cập nhật" : "Thêm sản phẩm"}</button></footer>
      </form>}
    </dialog>
  </section>;
}
