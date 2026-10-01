import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import "./InternalProfile.css";

const API = "http://127.0.0.1:8000/api";
async function profileRequest(path, options = {}) {
  const response = await fetch(`${API}${path}`, {
    ...options,
    headers: { Accept: "application/json", "Content-Type": "application/json", Authorization: `Bearer ${localStorage.getItem("token")}` },
  });
  const data = await response.json();
  if (!response.ok) {
    const error = new Error(response.status === 422 ? "Vui lòng kiểm tra thông tin bên dưới." : "Không thể cập nhật hồ sơ. Vui lòng kiểm tra phiên đăng nhập và thử lại.");
    error.fields = data.errors || {};
    throw error;
  }
  return data.taiKhoan;
}

export default function InternalProfile({ roleName }) {
  const navigate = useNavigate();
  const [tab, setTab] = useState("profile");
  const [passwords, setPasswords] = useState({ matKhauHienTai: "", matKhauMoi: "", matKhauMoi_confirmation: "" });
  const [passwordChanged, setPasswordChanged] = useState(false);
  const [account, setAccount] = useState(null);
  const [form, setForm] = useState(null);
  const [errors, setErrors] = useState({});
  const [message, setMessage] = useState("");
  const [success, setSuccess] = useState(false);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const dialog = useRef(null);

  useEffect(() => {
    const controller = new AbortController();
    profileRequest("/auth/me", { signal: controller.signal }).then(setAccount)
      .catch((error) => { if (error.name !== "AbortError") setMessage("Không thể tải hồ sơ. Vui lòng thử lại."); })
      .finally(() => { if (!controller.signal.aborted) setLoading(false); });
    return () => controller.abort();
  }, []);

  const open = async () => {
    setTab("profile");
    setPasswords({ matKhauHienTai: "", matKhauMoi: "", matKhauMoi_confirmation: "" });
    dialog.current.showModal();
    setLoading(true); setMessage(""); setErrors({}); setSuccess(false); setForm(null);
    try {
      const current = await profileRequest("/auth/me");
      setAccount(current);
      const employee = current.nhan_vien;
      if (!employee) { setMessage("Tài khoản chưa được liên kết với hồ sơ nhân viên. Vui lòng liên hệ quản lý."); return; }
      setForm({ hoTen: employee.hoTen || "", sdt: employee.sdt || "", email: employee.email || "" });
    } catch { setMessage("Không thể tải hồ sơ. Vui lòng đóng và thử lại."); }
    finally { setLoading(false); }
  };

  const save = async (event) => {
    event.preventDefault();
    if (saving) return;
    const values = Object.fromEntries(Object.entries(form).map(([key, value]) => [key, value.trim()]));
    const invalid = {};
    if (!values.hoTen) invalid.hoTen = ["Vui lòng nhập họ và tên."];
    if (!values.sdt) invalid.sdt = ["Vui lòng nhập số điện thoại."];
    else if (!/^0[0-9]{9}$/.test(values.sdt)) invalid.sdt = ["Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0."];
    if (!values.email) invalid.email = ["Vui lòng nhập email."];
    else if (!/^[A-Za-z0-9._%+-]+@gmail\.com$/i.test(values.email)) invalid.email = ["Vui lòng sử dụng địa chỉ Gmail hợp lệ."];
    setErrors(invalid); setMessage(""); setSuccess(false);
    if (Object.keys(invalid).length) return;
    setSaving(true);
    try {
      const updated = await profileRequest("/auth/internal/profile", { method: "PATCH", body: JSON.stringify(values) });
      setAccount(updated);
      localStorage.setItem("taiKhoan", JSON.stringify(updated));
      setForm({ hoTen: updated.nhan_vien.hoTen, sdt: updated.nhan_vien.sdt, email: updated.nhan_vien.email });
      setSuccess(true); setMessage("Đã lưu thông tin cá nhân. Hãy dùng email mới cho lần đăng nhập tiếp theo nếu bạn đã đổi email.");
    } catch (error) { setErrors(error.fields || {}); setMessage(error.fields ? error.message : "Không thể kết nối đến máy chủ. Vui lòng thử lại."); }
    finally { setSaving(false); }
  };

  const savePassword = async (event) => {
    event.preventDefault();
    if (saving) return;
    const invalid = {};
    if (!passwords.matKhauHienTai.trim()) invalid.matKhauHienTai = ["Vui lòng nhập mật khẩu hiện tại."];
    if (!passwords.matKhauMoi.trim()) invalid.matKhauMoi = ["Vui lòng nhập mật khẩu mới."];
    else if (passwords.matKhauMoi.length < 8) invalid.matKhauMoi = ["Mật khẩu mới phải có ít nhất 8 ký tự."];
    else if (passwords.matKhauMoi === passwords.matKhauHienTai) invalid.matKhauMoi = ["Mật khẩu mới phải khác mật khẩu hiện tại."];
    if (!passwords.matKhauMoi_confirmation) invalid.matKhauMoi_confirmation = ["Vui lòng xác nhận mật khẩu mới."];
    else if (passwords.matKhauMoi !== passwords.matKhauMoi_confirmation) invalid.matKhauMoi_confirmation = ["Mật khẩu xác nhận chưa khớp."];
    setErrors(invalid); setMessage(""); setSuccess(false);
    if (Object.keys(invalid).length) return;
    setSaving(true);
    try {
      await profileRequest("/auth/internal/password", { method: "PATCH", body: JSON.stringify(passwords) });
      ["token", "vaiTro", "taiKhoan", "khachHang"].forEach((key) => localStorage.removeItem(key));
      setPasswords({ matKhauHienTai: "", matKhauMoi: "", matKhauMoi_confirmation: "" });
      setPasswordChanged(true);
    } catch (error) { setErrors(error.fields || {}); setMessage(error.fields ? error.message : "Không thể kết nối đến máy chủ. Vui lòng thử lại."); }
    finally { setSaving(false); }
  };

  return <>
    <button type="button" className="dashboard-user internal-profile-trigger" onClick={open} aria-haspopup="dialog">
      <strong>{account?.nhan_vien?.hoTen || (loading ? "Đang tải…" : "Thông tin cá nhân")}</strong>
      <span>{roleName}</span><small>Xem và chỉnh sửa hồ sơ</small>
    </button>
    <dialog ref={dialog} className="internal-profile-dialog" onCancel={(event) => { if (saving || passwordChanged) event.preventDefault(); }}>
      <div className="internal-profile-heading"><h2>{tab === "password" ? "Đổi mật khẩu" : "Thông tin cá nhân"}</h2>{!passwordChanged && <button type="button" aria-label="Đóng" disabled={saving} onClick={() => dialog.current.close()}>×</button>}</div>
      {!passwordChanged && <div className="internal-profile-tabs">{[["profile", "Thông tin cá nhân"], ["password", "Đổi mật khẩu"]].map(([value, label]) => <button key={value} type="button" disabled={saving || loading} aria-pressed={tab === value} onClick={() => { setTab(value); setErrors({}); setMessage(""); setSuccess(false); setPasswords({ matKhauHienTai: "", matKhauMoi: "", matKhauMoi_confirmation: "" }); }}>{label}</button>)}</div>}
      {passwordChanged && <div role="status"><p className="internal-profile-success">Đổi mật khẩu thành công. Vui lòng đăng nhập lại bằng mật khẩu mới.</p><button type="button" className="internal-profile-save" onClick={() => navigate("/internal/login", { replace: true })}>Đăng nhập lại</button></div>}
      {message && <p className={success ? "internal-profile-success" : "internal-profile-error"} role={success ? "status" : "alert"}>{message}</p>}
      {!passwordChanged && tab === "password" && <form onSubmit={savePassword} noValidate><fieldset disabled={saving}>
        {[["matKhauHienTai", "Mật khẩu hiện tại"], ["matKhauMoi", "Mật khẩu mới"], ["matKhauMoi_confirmation", "Xác nhận mật khẩu mới"]].map(([name, label]) => <label key={name}>{label} *<input name={name} type="password" autoComplete={name === "matKhauHienTai" ? "current-password" : "new-password"} required maxLength={name === "matKhauHienTai" ? 255 : 72} value={passwords[name]} aria-invalid={Boolean(errors[name])} aria-describedby={errors[name] ? `internal-${name}-error` : undefined} onChange={(event) => setPasswords({ ...passwords, [name]: event.target.value })} />{errors[name] && <small id={`internal-${name}-error`} className="internal-profile-error">{errors[name][0]}</small>}</label>)}
        <p className="internal-profile-meta">Mật khẩu mới có từ 8 đến 72 ký tự. Sau khi đổi, các phiên đăng nhập sẽ kết thúc.</p>
        <button type="submit" className="internal-profile-save">{saving ? "Đang lưu…" : "Đổi mật khẩu"}</button>
      </fieldset></form>}
      {!passwordChanged && tab === "profile" && (loading ? <p role="status">Đang tải hồ sơ…</p> : form && <form onSubmit={save} noValidate>
        <fieldset disabled={saving}>
          {[["hoTen", "Họ và tên", "text"], ["sdt", "Số điện thoại", "tel"], ["email", "Email đăng nhập", "email"]].map(([name, label, type]) => <label key={name}>
            {label} <span aria-hidden="true">*</span>
            <input name={name} type={type} required maxLength={name === "sdt" ? 10 : 255} value={form[name]} aria-invalid={Boolean(errors[name])} aria-describedby={errors[name] ? `internal-${name}-error` : undefined} onChange={(event) => { setForm({ ...form, [name]: event.target.value }); setErrors({ ...errors, [name]: undefined }); }} />
            {errors[name] && <small id={`internal-${name}-error`} className="internal-profile-error">{errors[name][0]}</small>}
          </label>)}
          <div className="internal-profile-meta"><p>Vai trò: <strong>{roleName}</strong></p><p>Mã nhân viên: {account?.nhan_vien?.maNV}</p></div>
          <button className="internal-profile-save" type="submit">{saving ? "Đang lưu…" : "Lưu thay đổi"}</button>
        </fieldset>
      </form>)}
    </dialog>
  </>;
}
