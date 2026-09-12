import { useEffect, useRef, useState } from "react";
import { getComboProducts, getManagedCombos, saveCombo, stopCombo } from "../services/comboManagement";
import "./ComboManagement.css";

const money = (value) => new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(Number(value));
const statuses = { HOAT_DONG: "Đang bán", NGUNG_HOAT_DONG: "Ngừng bán" };
const blankForm = { maCombo: "", tenCombo: "", donGia: "", moTa: "", hinhAnh: "", trangThai: "HOAT_DONG", sanPhams: [{ maSP: "", soLuong: 1 }] };

function ComboImage({ src, title }) {
  const [failed, setFailed] = useState(false);
  return src && !failed
    ? <img className="combo-admin-image" src={src} alt={title} loading="lazy" onError={() => setFailed(true)} />
    : <div className="combo-admin-image empty">{src ? "Không tải được ảnh" : "Chưa có ảnh"}</div>;
}

export default function ComboManagement() {
  const manager = localStorage.getItem("vaiTro") === "QUAN_LY";
  const [result, setResult] = useState({ data: [], total: 0, last_page: 1 });
  const [query, setQuery] = useState({ keyword: "", status: "", page: 1 });
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");
  const [revision, setRevision] = useState(0);
  const [form, setForm] = useState(null);
  const [editing, setEditing] = useState(null);
  const [formError, setFormError] = useState("");
  const [products, setProducts] = useState([]);
  const [productsLoading, setProductsLoading] = useState(false);
  const [productsError, setProductsError] = useState("");
  const [image, setImage] = useState(null);
  const [preview, setPreview] = useState("");
  const [busy, setBusy] = useState(false);
  const dialog = useRef(null);
  const productRequest = useRef(null);

  useEffect(() => {
    const controller = new AbortController();
    getManagedCombos(query, controller.signal).then((data) => {
      if (!controller.signal.aborted) setResult(data);
    }).catch((err) => { if (!controller.signal.aborted) setError(err.message); })
      .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, [query, revision]);
  useEffect(() => () => { if (preview) URL.revokeObjectURL(preview); }, [preview]);
  useEffect(() => () => productRequest.current?.abort(), []);

  const changeQuery = (next) => { setLoading(true); setError(""); setQuery(next); };
  const refresh = () => { setLoading(true); setError(""); setRevision((value) => value + 1); };
  const loadProducts = async () => {
    productRequest.current?.abort();
    const controller = new AbortController();
    productRequest.current = controller;
    setProductsLoading(true); setProductsError("");
    try {
      const data = await getComboProducts(controller.signal);
      if (!controller.signal.aborted) setProducts(data);
    } catch (err) { if (!controller.signal.aborted) setProductsError(err.message); }
    finally { if (!controller.signal.aborted) setProductsLoading(false); }
  };
  const open = (combo) => {
    setEditing(combo?.maCombo || null);
    const lines = combo?.chi_tiet_combos || combo?.chiTietCombos || [];
    setForm(combo ? {
      maCombo: combo.maCombo,
      tenCombo: combo.tenCombo, donGia: combo.donGia, moTa: combo.moTa || "", hinhAnh: combo.hinhAnh || "", trangThai: combo.trangThai,
      sanPhams: lines.map((line) => ({ maSP: line.maSP, soLuong: line.soLuong, name: line.san_pham?.tenSP || line.sanPham?.tenSP || line.maSP })),
    } : { ...blankForm, sanPhams: [{ maSP: "", soLuong: 1 }] });
    setFormError(""); setImage(null); setPreview(""); setMessage("");
    dialog.current.showModal();
    loadProducts();
  };
  const close = () => { dialog.current.close(); productRequest.current?.abort(); setForm(null); setImage(null); setPreview(""); };
  const change = (name, value) => { setForm({ ...form, [name]: value }); setFormError(""); };
  const changeLine = (index, name, value) => change("sanPhams", form.sanPhams.map((line, i) => i === index ? { ...line, [name]: value } : line));
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
    if (busy || productsLoading || productsError) return;
    setFormError("");
    if (!form.maCombo.trim()) { setFormError("Vui lòng nhập mã combo."); return; }
    if (!form.tenCombo.trim()) { setFormError("Vui lòng nhập tên combo."); return; }
    if (!form.sanPhams.length) { setFormError("Combo phải có ít nhất một sản phẩm."); return; }
    const lines = form.sanPhams.map(({ maSP, soLuong }) => ({ maSP, soLuong: Number(soLuong) }));
    if (lines.some((line) => !products.some((product) => product.maSP === line.maSP))) {
      setFormError("Vui lòng chọn sản phẩm đang bán cho tất cả các dòng."); return;
    }
    if (new Set(lines.map((line) => line.maSP)).size !== lines.length) {
      setFormError("Sản phẩm bị trùng. Hãy tăng số lượng trên cùng một dòng."); return;
    }
    if (lines.some((line) => !Number.isInteger(line.soLuong) || line.soLuong < 1 || line.soLuong > 1000)) {
      setFormError("Số lượng mỗi sản phẩm phải là số nguyên từ 1 đến 1000."); return;
    }
    setBusy(true);
    try {
      await saveCombo(editing, { ...form, maCombo: form.maCombo.trim(), tenCombo: form.tenCombo.trim(), moTa: form.moTa.trim(), sanPhams: lines }, image);
      close(); setMessage(editing ? "Cập nhật combo thành công." : "Thêm combo thành công."); refresh();
    } catch (err) { setFormError(err.message); }
    finally { setBusy(false); }
  };
  const stop = async (combo) => {
    if (busy || !window.confirm(`Ngừng bán combo “${combo.tenCombo}”? Lịch sử đơn hàng vẫn được giữ lại.`)) return;
    setBusy(true); setMessage(""); setError("");
    try { await stopCombo(combo.maCombo); setMessage("Đã ngừng bán combo."); refresh(); }
    catch (err) { setError(err.message); }
    finally { setBusy(false); }
  };

  return <section className="combo-admin">
    <header className="combo-admin-heading"><div><h1>Quản lý combo</h1><p>Danh sách combo bắp nước, giá bán và sản phẩm đi kèm.</p></div>{manager && <button disabled={busy} onClick={() => open()}>+ Thêm combo</button>}</header>
    <form className="combo-admin-filters" onSubmit={(event) => { event.preventDefault(); changeQuery({ ...query, keyword: search.trim(), page: 1 }); }}>
      <input aria-label="Tìm combo" placeholder="Tìm theo tên combo..." maxLength="100" value={search} onChange={(event) => setSearch(event.target.value)} />
      <select aria-label="Lọc trạng thái" value={query.status} onChange={(event) => changeQuery({ ...query, status: event.target.value, page: 1 })}><option value="">Tất cả trạng thái</option>{Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select>
      <button>Tìm kiếm</button><button type="button" className="secondary" disabled={loading || busy} onClick={refresh}>Tải lại</button>
    </form>
    {error && <p className="combo-admin-error" role="alert">{error}</p>}{message && <p className="combo-admin-success" role="status">{message}</p>}
    <div className="combo-admin-table"><table><thead><tr><th>Ảnh</th><th>Combo</th><th>Sản phẩm đi kèm</th><th>Giá bán</th><th>Trạng thái</th>{manager && <th>Thao tác</th>}</tr></thead><tbody>
      {loading ? <tr><td colSpan={manager ? 6 : 5}>Đang tải combo...</td></tr> : result.data.length === 0 ? <tr><td colSpan={manager ? 6 : 5}>Không có combo phù hợp.</td></tr> : result.data.map((combo) => <tr key={combo.maCombo}>
        <td><ComboImage key={combo.hinhAnh} src={combo.hinhAnh} title={combo.tenCombo} /></td>
        <td><strong>{combo.tenCombo}</strong><small className="combo-admin-code">{combo.maCombo}</small><p className="combo-admin-description">{combo.moTa}</p></td>
        <td><ul>{(combo.chi_tiet_combos || combo.chiTietCombos || []).map((line) => <li key={line.maCTCombo}>{line.soLuong} × {line.san_pham?.tenSP || line.sanPham?.tenSP || line.maSP}</li>)}</ul></td>
        <td className="combo-admin-price">{money(combo.donGia)}</td><td><span className={`combo-admin-status ${combo.trangThai}`}>{statuses[combo.trangThai]}</span></td>
        {manager && <td><div className="combo-admin-actions"><button className="secondary" disabled={busy} onClick={() => open(combo)}>Sửa</button>{combo.trangThai === "HOAT_DONG" && <button disabled={busy} onClick={() => stop(combo)}>Ngừng bán</button>}</div></td>}
      </tr>)}
    </tbody></table></div>
    <footer className="combo-admin-pagination"><span>{result.total} combo · Trang {query.page}/{result.last_page}</span><button disabled={loading || query.page <= 1} onClick={() => changeQuery({ ...query, page: query.page - 1 })}>Trước</button><button disabled={loading || query.page >= result.last_page} onClick={() => changeQuery({ ...query, page: query.page + 1 })}>Sau</button></footer>
    <dialog ref={dialog} className="combo-admin-dialog" aria-labelledby="combo-form-title" onCancel={(event) => { event.preventDefault(); if (!busy) close(); }}>
      {form && <form onSubmit={submit}>
        <header className="combo-admin-heading"><h2 id="combo-form-title">{editing ? "Sửa combo" : "Thêm combo"}</h2><button type="button" className="secondary" disabled={busy} aria-label="Đóng" onClick={close}>×</button></header>
        <div className="combo-admin-grid">
          <label>Mã combo *<input required maxLength="50" pattern="[A-Za-z0-9_-]+" title="Chữ cái không dấu, số, dấu gạch ngang và gạch dưới" placeholder="VD: CB001" value={form.maCombo} disabled={busy || Boolean(editing)} onChange={(event) => change("maCombo", event.target.value)} />{editing && <small>Mã combo được giữ cố định sau khi tạo.</small>}</label>
          <label>Tên combo *<input required maxLength="255" value={form.tenCombo} disabled={busy} onChange={(event) => change("tenCombo", event.target.value)} /></label>
          <label>Giá bán (đồng) *<input type="number" required min="0" max="99999999.99" step="0.01" value={form.donGia} disabled={busy} onChange={(event) => change("donGia", event.target.value)} /></label>
          <label>Trạng thái<select value={form.trangThai} disabled={busy} onChange={(event) => change("trangThai", event.target.value)}>{Object.entries(statuses).map(([value, label]) => <option key={value} value={value}>{label}</option>)}</select></label>
          <label>Ảnh combo<input type="file" accept="image/jpeg,image/png,image/webp" disabled={busy} onChange={chooseImage} /><small>JPG, PNG hoặc WebP, tối đa 2 MB. Không chọn ảnh mới sẽ giữ ảnh hiện tại.</small></label>
          {(preview || form.hinhAnh) && <ComboImage key={preview || form.hinhAnh} src={preview || form.hinhAnh} title={form.tenCombo || "Ảnh combo"} />}
          <label className="full">Mô tả<textarea rows="3" maxLength="5000" value={form.moTa} disabled={busy} onChange={(event) => change("moTa", event.target.value)} /></label>
        </div>
        <fieldset className="combo-admin-products" disabled={busy || productsLoading}>
          <legend>Sản phẩm trong combo *</legend>
          {productsLoading && <p>Đang tải sản phẩm...</p>}
          {productsError && <p className="combo-admin-error" role="alert">{productsError} <button type="button" onClick={loadProducts}>Thử lại</button></p>}
          {!productsLoading && !productsError && products.length === 0 && <p>Chưa có sản phẩm đang bán để tạo combo.</p>}
          {form.sanPhams.map((line, index) => <div className="combo-admin-product-row" key={index}>
            <label>Sản phẩm {index + 1}<select required value={line.maSP} onChange={(event) => changeLine(index, "maSP", event.target.value)}>
              <option value="">Chọn sản phẩm</option>
              {line.maSP && !products.some((product) => product.maSP === line.maSP) && <option value={line.maSP} disabled>{line.name || line.maSP} (không còn bán)</option>}
              {products.map((product) => <option key={product.maSP} value={product.maSP} disabled={form.sanPhams.some((other, otherIndex) => otherIndex !== index && other.maSP === product.maSP)}>{product.tenSP} · {money(product.donGia)}</option>)}
            </select></label>
            <label>Số lượng<input type="number" min="1" max="1000" step="1" required value={line.soLuong} onChange={(event) => changeLine(index, "soLuong", event.target.value)} /></label>
            <button type="button" className="secondary" aria-label={`Bỏ sản phẩm ${index + 1}`} onClick={() => change("sanPhams", form.sanPhams.filter((_, i) => i !== index))}>Bỏ</button>
          </div>)}
          <button type="button" className="secondary" disabled={form.sanPhams.length >= 100 || form.sanPhams.length >= products.length} onClick={() => change("sanPhams", [...form.sanPhams, { maSP: "", soLuong: 1 }])}>+ Thêm sản phẩm</button>
        </fieldset>
        {formError && <p className="combo-admin-error" role="alert">{formError}</p>}
        <footer className="combo-admin-actions"><button type="button" className="secondary" disabled={busy} onClick={close}>Hủy</button><button disabled={busy || productsLoading || Boolean(productsError) || !products.length}>{busy ? "Đang lưu..." : editing ? "Cập nhật" : "Thêm combo"}</button></footer>
      </form>}
    </dialog>
  </section>;
}
