import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { changeMemberPassword, loadMember, saveMember } from "../services/memberService";
import "./Membership.css";

const emptyPasswords = { matKhauHienTai: "", matKhauMoi: "", matKhauMoi_confirmation: "" };
const profileValues = (member) => ({
    hoTen: member.hoTen || "", email: member.email || "", soDienThoai: member.soDienThoai || "",
    ngaySinh: member.ngaySinh?.slice(0, 10) || "", gioiTinh: member.gioiTinh || "",
});

function Icon({ name, ...props }) {
    const paths = {
        user: <><circle cx="12" cy="8" r="4" /><path d="M4 21v-2a8 8 0 0 1 16 0v2" /></>,
        ticket: <><path d="M3 5h18v5a2 2 0 0 0 0 4v5H3v-5a2 2 0 0 0 0-4Z" /><path d="M15 5v3m0 3v2m0 3v3" /></>,
        lock: <><rect x="5" y="10" width="14" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2" /></>,
        gift: <><path d="M3 8h18v4H3zm2 4v9h14v-9M12 8v13" /><path d="M12 8C2 8 6 0 10 4Zm0 0c10 0 6-8 2-4Z" /></>,
        arrow: <path d="M5 12h14m-6-6 6 6-6 6" />,
    };
    return <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true" {...props}>{paths[name]}</svg>;
}

export default function Membership() {
    const navigate = useNavigate();
    const [member, setMember] = useState(null);
    const [form, setForm] = useState(null);
    const [passwords, setPasswords] = useState(emptyPasswords);
    const [tab, setTab] = useState("profile");
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [reload, setReload] = useState(0);
    const [notice, setNotice] = useState(null);
    const [errors, setErrors] = useState({});
    const [passwordChanged, setPasswordChanged] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        loadMember(controller.signal).then(({ data }) => {
            setMember(data);
            setForm(profileValues(data));
        }).catch((error) => {
            if (error.name === "AbortError") return;
            if (error.status === 401) {
                navigate("/login", { replace: true, state: { from: "/thanh-vien" } });
                return;
            }
            setNotice({ type: "error", text: "Không thể tải hồ sơ. Vui lòng kiểm tra kết nối và thử lại." });
        }).finally(() => { if (!controller.signal.aborted) setLoading(false); });
        return () => controller.abort();
    }, [navigate, reload]);

    const selectTab = (value) => {
        setTab(value);
        setErrors({});
        setNotice(null);
        setPasswords(emptyPasswords);
    };

    const submit = async (event) => {
        event.preventDefault();
        setErrors({});
        setNotice(null);
        if (tab === "security" && passwords.matKhauMoi !== passwords.matKhauMoi_confirmation) {
            setErrors({ matKhauMoi_confirmation: ["Mật khẩu xác nhận chưa khớp."] });
            return;
        }
        setSaving(true);
        try {
            if (tab === "profile") {
                const { data } = await saveMember({ ...form, hoTen: form.hoTen.trim(), email: form.email.trim(), ngaySinh: form.ngaySinh || null, gioiTinh: form.gioiTinh || null });
                setMember(data);
                setForm(profileValues(data));
                localStorage.setItem("khachHang", JSON.stringify(data));
                window.dispatchEvent(new Event("customer-profile-updated"));
                setNotice({ type: "success", text: "Đã lưu thông tin cá nhân của bạn." });
            } else {
                await changeMemberPassword(passwords);
                ["token", "vaiTro", "taiKhoan", "khachHang"].forEach((key) => localStorage.removeItem(key));
                window.dispatchEvent(new Event("customer-profile-updated"));
                setPasswords(emptyPasswords);
                setPasswordChanged(true);
            }
        } catch (error) {
            if (error.status === 401) {
                navigate("/login", { replace: true, state: { from: "/thanh-vien" } });
                return;
            }
            setErrors(error.fields || {});
            setNotice({ type: "error", text: Object.keys(error.fields || {}).length ? "Vui lòng kiểm tra các thông tin bên dưới." : error.message });
        } finally { setSaving(false); }
    };

    const fieldError = (name) => errors[name] && <span id={`${name}-error`} className="member-field-error">{errors[name][0]}</span>;
    const fieldProps = (name) => ({ "aria-invalid": Boolean(errors[name]), "aria-describedby": errors[name] ? `${name}-error` : undefined });
    const today = new Date();
    const maxBirthday = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;

    return (
        <div className="member-page">
            <div className="member-container">
                <nav className="member-breadcrumb" aria-label="Đường dẫn"><Link to="/home">Trang chủ</Link><span>/</span><span>Thành viên</span></nav>
                <div className="member-heading"><div><p className="member-eyebrow">CGV MEMBERSHIP</p><h1>Thông tin thành viên</h1><p>Thông tin của bạn, những trải nghiệm điện ảnh của riêng bạn.</p></div><span className="member-heading-mark" aria-hidden="true">✳</span></div>

                {passwordChanged ? <section className="member-panel member-state" role="status"><Icon name="lock" /><h2>Đổi mật khẩu thành công</h2><p>Vui lòng đăng nhập lại bằng mật khẩu mới để tiếp tục.</p><Link className="member-button" to="/login" state={{ from: "/thanh-vien" }}>Đăng nhập lại <Icon name="arrow" /></Link></section> : loading ? <section className="member-panel member-state" role="status"><span className="member-spinner" /><p>Đang tải thông tin thành viên…</p></section> : !member ? <section className="member-panel member-state" role="alert"><h2>Chưa thể tải thông tin</h2><p>{notice?.text}</p><button className="member-button" onClick={() => { setLoading(true); setNotice(null); setReload((value) => value + 1); }}>Thử lại</button></section> : (
                    <div className="member-layout">
                        <aside className="member-sidebar">
                            <div className="member-identity"><div className="member-avatar">{member.hoTen?.trim().split(/\s+/).slice(-2).map((word) => word[0]).join("") || "CGV"}</div><h2>{member.hoTen}</h2><span>Thành viên CGV</span></div>
                            <nav className="member-nav" aria-label="Tài khoản thành viên">
                                <button aria-current={tab === "profile" ? "page" : undefined} disabled={saving} onClick={() => selectTab("profile")}><Icon name="user" />Thông tin cá nhân<span>›</span></button>
                                <Link to="/my-tickets"><Icon name="ticket" />Vé của tôi<span>↗</span></Link>
                                <button aria-current={tab === "security" ? "page" : undefined} disabled={saving} onClick={() => selectTab("security")}><Icon name="lock" />Đổi mật khẩu<span>›</span></button>
                                <Link to="/tin-tuc"><Icon name="gift" />Tin tức & ưu đãi<span>↗</span></Link>
                            </nav>
                            <div className="member-sidebar-note"><span aria-hidden="true">✦</span><p>Mỗi bộ phim là một hành trình.<br />Cùng CGV viết tiếp câu chuyện của bạn.</p></div>
                        </aside>
                        <div className="member-content">
                            <section className="member-overview" aria-label="Thông tin thành viên">
                                <div className="member-welcome"><span className="member-small-label">RẤT VUI ĐƯỢC GẶP BẠN</span><h2>Hẹn bạn ở rạp!</h2><p>Chọn một bộ phim hay và dành thời gian cho những điều bạn yêu thích.</p><Link to="/movies">Khám phá phim <Icon name="arrow" /></Link>{member.ngayDangKy && <small>Đồng hành từ {new Date(member.ngayDangKy).toLocaleDateString("vi-VN")}</small>}</div>
                            </section>
                            <section className="member-panel">
                                <div className="member-panel-heading"><div><h2>{tab === "profile" ? "Thông tin cá nhân" : "Bảo mật tài khoản"}</h2><p>{tab === "profile" ? "Cập nhật hồ sơ để CGV có thể đồng hành cùng bạn tốt hơn." : "Sử dụng mật khẩu riêng để bảo vệ tài khoản của bạn."}</p></div><Icon name={tab === "profile" ? "user" : "lock"} /></div>
                                {notice && <div className={`member-notice ${notice.type}`} role={notice.type === "error" ? "alert" : "status"}>{notice.text}</div>}
                                <form onSubmit={submit}>
                                    <fieldset disabled={saving} className="member-fields">
                                        {tab === "profile" ? <div className="member-form-grid">
                                            <label>Họ và tên <span>*</span><input name="hoTen" autoComplete="name" value={form.hoTen} onChange={(e) => setForm({ ...form, hoTen: e.target.value })} required maxLength={255} {...fieldProps("hoTen")} />{fieldError("hoTen")}</label>
                                            <label>Số điện thoại <span>*</span><input name="soDienThoai" type="tel" autoComplete="tel" value={form.soDienThoai} onChange={(e) => setForm({ ...form, soDienThoai: e.target.value })} required maxLength={20} {...fieldProps("soDienThoai")} />{fieldError("soDienThoai")}</label>
                                            <label className="member-field-wide">Email <span>*</span><input name="email" type="email" autoComplete="email" value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} required maxLength={255} {...fieldProps("email")} />{fieldError("email")}</label>
                                            <label>Ngày sinh<input name="ngaySinh" type="date" autoComplete="bday" max={maxBirthday} value={form.ngaySinh} onChange={(e) => setForm({ ...form, ngaySinh: e.target.value })} {...fieldProps("ngaySinh")} />{fieldError("ngaySinh")}</label>
                                            <label>Giới tính<select name="gioiTinh" value={form.gioiTinh} onChange={(e) => setForm({ ...form, gioiTinh: e.target.value })} {...fieldProps("gioiTinh")}><option value="">Chưa cung cấp</option><option value="NAM">Nam</option><option value="NU">Nữ</option></select>{fieldError("gioiTinh")}</label>
                                        </div> : <div className="member-password-fields">{[["matKhauHienTai", "Mật khẩu hiện tại"], ["matKhauMoi", "Mật khẩu mới"], ["matKhauMoi_confirmation", "Xác nhận mật khẩu mới"]].map(([name, label]) => <label key={name}>{label} <span>*</span><input type="password" name={name} autoComplete={name === "matKhauHienTai" ? "current-password" : "new-password"} required minLength={name === "matKhauHienTai" ? undefined : 8} maxLength={name === "matKhauHienTai" ? 255 : 72} value={passwords[name]} onChange={(e) => setPasswords({ ...passwords, [name]: e.target.value })} {...fieldProps(name)} />{fieldError(name)}</label>)}<p className="member-form-hint">Mật khẩu mới có ít nhất 8 ký tự và khác mật khẩu hiện tại. Sau khi đổi, bạn sẽ cần đăng nhập lại trên các thiết bị.</p></div>}
                                        <div className="member-form-footer"><span><Icon name="lock" />Thông tin của bạn được bảo mật</span><button className="member-button" type="submit">{saving ? "Đang lưu…" : tab === "profile" ? "Lưu thay đổi" : "Đổi mật khẩu"}{!saving && <Icon name="arrow" />}</button></div>
                                    </fieldset>
                                </form>
                            </section>
                            <Link className="member-ticket-banner" to="/my-tickets"><span className="member-banner-icon"><Icon name="ticket" /></span><div><h3>Những cuộc hẹn với màn ảnh rộng</h3><p>Xem vé đã đặt và thông tin các suất chiếu của bạn.</p></div><Icon name="arrow" /></Link>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
