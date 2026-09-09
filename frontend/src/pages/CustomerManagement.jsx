import { useEffect, useRef, useState } from "react";
import "./CustomerManagement.css";
import { customerRequest, useCustomerData } from "../services/customerManagement";

const money = (value) => new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND" }).format(value);
const date = (value) => value ? new Intl.DateTimeFormat("vi-VN").format(new Date(value.length === 10 ? `${value}T00:00:00` : value)) : "—";

function Modal({ title, children, onClose }) {
  const ref = useRef(null);
  useEffect(() => {
    const previous = document.activeElement;
    ref.current.showModal();
    const overflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = overflow;
      previous?.focus();
    };
  }, []);
  return (
    <dialog ref={ref} className="cm-dialog" aria-labelledby="cm-dialog-title" onCancel={(event) => { event.preventDefault(); onClose(); }} onClick={(event) => { if (event.target === event.currentTarget) onClose(); }}>
      <div className="cm-dialog-content">
        <header className="cm-heading"><h2 id="cm-dialog-title">{title}</h2><button type="button" onClick={onClose} aria-label="Đóng hộp thoại">✕</button></header>
        {children}
      </div>
    </dialog>
  );
}

export default function CustomerManagement() {
  const [revision, setRevision] = useState(0);
  const [deleting, setDeleting] = useState(false);
  const [deleteError, setDeleteError] = useState("");
  const [query, setQuery] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);
  const [modal, setModal] = useState(null);
  const [notice, setNotice] = useState("");
  const params = new URLSearchParams({ q: query.trim(), trangThai: status, page });
  const { data, error, loading } = useCustomerData('?' + params, revision);
  const visible = data?.data || [];
  const total = data?.total || 0;
  const pages = data?.last_page || 1;
  const currentPage = data?.current_page || page;
  const stats = data?.stats;
  const locking = modal?.customer.trangThai === "HOAT_DONG";

  function openModal(type, customer) {
    setDeleteError("");
    setModal({ type, customer });
  }

  async function removeCustomer() {
    if (deleting) return;
    setDeleting(true);
    setDeleteError("");
    try {
      const result = await customerRequest('/' + encodeURIComponent(modal.customer.maKH) + (modal.type === "status" ? '/trang-thai' : ''), modal.type === "status" ? {
        method: "PATCH",
        body: JSON.stringify({ trangThai: locking ? "KHOA" : "HOAT_DONG" }),
      } : { method: "DELETE" });
      setNotice(result.message);
      setModal(null);
      if (visible.length === 1 && page > 1 && (modal.type === "delete" || status)) setPage(page - 1);
      setRevision((value) => value + 1);
    } catch (error) {
      setDeleteError(error.message);
    } finally {
      setDeleting(false);
    }
  }

  return (
    <section className="cm-page">
      <p className="cm-breadcrumb">Hệ thống quản lý / Tài khoản</p>
      <header className="cm-heading">
        <div><h1>Quản lý tài khoản</h1><p>Tra cứu tài khoản và theo dõi lịch sử giao dịch của khách hàng.</p></div>
        <span className="cm-role">Dành cho quản lý</span>
      </header>
      {notice && <div className="cm-notice" role="status">{notice}<button type="button" onClick={() => setNotice("")} aria-label="Đóng thông báo">✕</button></div>}
      <div className="cm-stats">
        <article><span>Tổng khách hàng</span><strong>{stats?.total ?? "—"}</strong><small>Tài khoản trong danh sách</small></article>
        <article><span>Đang hoạt động</span><strong>{stats?.active ?? "—"}</strong><small>Tài khoản đang được sử dụng</small></article>
        <article><span>Đã khóa</span><strong>{stats?.locked ?? "—"}</strong><small>Tài khoản ngừng truy cập</small></article>
      </div>
      <section className="cm-card" aria-labelledby="cm-list-title">
        <div className="cm-card-heading"><h2 id="cm-list-title">Danh sách tài khoản <span>{total}</span></h2></div>
        <div className="cm-filters">
          <label className="cm-search">Tìm kiếm tài khoản<input type="search" maxLength={255} placeholder="Nhập thông tin khách hàng…" value={query} onChange={(event) => { setQuery(event.target.value); setPage(1); }} /></label>
          <label>Trạng thái<select value={status} onChange={(event) => { setStatus(event.target.value); setPage(1); }}><option value="">Tất cả trạng thái</option><option value="HOAT_DONG">Đang hoạt động</option><option value="KHOA">Đã khóa</option></select></label>
          <button type="button" onClick={() => { setQuery(""); setStatus(""); setPage(1); }}>Xóa bộ lọc</button>
        </div>
        {loading && <p className="cm-empty" role="status">Đang tải khách hàng…</p>}
        {error && <div className="cm-error" role="alert">{error} <button type="button" onClick={() => setRevision((value) => value + 1)}>Thử lại</button></div>}
        <div className="cm-table-wrap" aria-busy={loading}>
          <table><thead><tr><th>Khách hàng</th><th>Liên hệ</th><th>Ngày đăng ký</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
            <tbody>{visible.map((customer) => <tr key={customer.maKH}>
              <td><div className="cm-person"><span className="cm-avatar" aria-hidden="true">{customer.hoTen.split(" ").at(-1).charAt(0)}</span><div><strong>{customer.hoTen}</strong><small>{customer.maKH} · @{customer.tenDangNhap}</small></div></div></td>
              <td>{customer.email}<small>{customer.soDienThoai}</small></td><td>{date(customer.ngayDangKy)}</td>
              <td><span className={`cm-badge ${customer.trangThai === "HOAT_DONG" ? "cm-green" : "cm-gray"}`}>{customer.trangThai === "HOAT_DONG" ? "Đang hoạt động" : "Đã khóa"}</span></td>
              <td><div className="cm-actions"><button type="button" aria-label={`Lịch sử giao dịch của ${customer.hoTen}`} onClick={() => openModal("history", customer)}>Lịch sử giao dịch</button><button type="button" onClick={() => openModal("status", customer)} aria-label={`${customer.trangThai === "HOAT_DONG" ? "Khóa" : "Mở khóa"} tài khoản ${customer.tenDangNhap}`}>{customer.trangThai === "HOAT_DONG" ? "Khóa" : "Mở khóa"}</button><button type="button" className="cm-danger" aria-label={`Xóa tài khoản ${customer.tenDangNhap}`} onClick={() => openModal("delete", customer)}>Xóa</button></div></td>
            </tr>)}</tbody>
          </table>
          {!loading && !error && !visible.length && <div className="cm-empty"><h3>Không tìm thấy khách hàng</h3><p>Thử từ khóa khác hoặc xóa bộ lọc để xem lại danh sách.</p></div>}
        </div>
        <footer className="cm-pagination"><span aria-live="polite">Hiển thị {total ? (currentPage - 1) * 5 + 1 : 0}–{Math.min(currentPage * 5, total)} / {total} khách hàng</span><div><button type="button" disabled={loading || !!error || currentPage === 1} onClick={() => setPage(currentPage - 1)}>Trước</button><span>Trang {currentPage} / {pages}</span><button type="button" disabled={loading || !!error || currentPage >= pages} onClick={() => setPage(currentPage + 1)}>Sau</button></div></footer>
      </section>
      {modal && <Modal title={modal.type === "status" ? (locking ? "Khóa tài khoản?" : "Mở khóa tài khoản?") : modal.type === "delete" ? "Xóa tài khoản khách hàng?" : "Lịch sử giao dịch"} onClose={() => { if (!deleting) setModal(null); }}>
        {modal.type === "status" ? <>
          <p>{locking ? "Khóa" : "Mở khóa"} tài khoản <strong>{modal.customer.tenDangNhap}</strong> của <strong>{modal.customer.hoTen}</strong>?</p>
          <p className="cm-demo">{locking ? "Khách hàng sẽ không thể đăng nhập và các phiên đăng nhập hiện tại sẽ bị thu hồi. Bạn có thể mở khóa lại sau." : "Khách hàng có thể đăng nhập lại bằng tài khoản hiện tại."}</p>
          {deleteError && <p className="cm-error" role="alert">{deleteError}</p>}
          <footer className="cm-modal-actions"><button type="button" autoFocus disabled={deleting} onClick={() => setModal(null)}>Hủy</button><button type="button" disabled={deleting} onClick={removeCustomer}>{deleting ? "Đang cập nhật…" : locking ? "Xác nhận khóa" : "Xác nhận mở khóa"}</button></footer>
        </> : modal.type === "delete" ? <>
          <p>Bạn muốn xóa tài khoản <strong>{modal.customer.tenDangNhap}</strong> của <strong>{modal.customer.hoTen}</strong> ({modal.customer.maKH})?</p>
          <p className="cm-demo">Tài khoản đăng nhập sẽ bị xóa vĩnh viễn và mọi phiên đăng nhập sẽ bị thu hồi. Hồ sơ khách hàng và lịch sử giao dịch được giữ lại.</p>
          {deleteError && <p className="cm-error" role="alert">{deleteError}</p>}
          <footer className="cm-modal-actions"><button type="button" autoFocus disabled={deleting} onClick={() => setModal(null)}>Hủy</button><button type="button" disabled={deleting} className="cm-delete-button" onClick={removeCustomer}>{deleting ? "Đang xóa…" : "Xác nhận xóa"}</button></footer>
        </> : <>
          <p><strong>{modal.customer.hoTen}</strong> · {modal.customer.maKH}</p><p className="cm-muted">{modal.customer.email} · {modal.customer.soDienThoai}</p>
          <CustomerHistory maKH={modal.customer.maKH} />
          <footer className="cm-modal-actions"><button type="button" onClick={() => setModal(null)}>Đóng</button></footer>
        </>}
      </Modal>}
    </section>
  );
}


function CustomerHistory({ maKH }) {
  const [page, setPage] = useState(1);
  const [revision, setRevision] = useState(0);
  const { data, error, loading } = useCustomerData('/' + encodeURIComponent(maKH) + '/giao-dich?page=' + page, revision);
  const labels = { DA_THANH_TOAN: "Đã thanh toán", CHO_THANH_TOAN: "Chờ thanh toán", DA_HUY: "Đã hủy", HET_HAN: "Hết hạn" };
  if (loading) return <p className="cm-empty" role="status">Đang tải lịch sử giao dịch…</p>;
  if (error) return <div className="cm-error" role="alert">{error} <button type="button" onClick={() => setRevision((value) => value + 1)}>Thử lại</button></div>;
  return <>
    <div className="cm-history-summary"><span>{data.total} giao dịch</span><strong>Đã thanh toán: {money(data.paidTotal)}</strong></div>
    {data.data.length ? <div className="cm-table-wrap"><table><thead><tr><th>Đơn hàng</th><th>Nội dung</th><th>Số tiền</th><th>Trạng thái</th></tr></thead><tbody>{data.data.map((item) => <tr key={item.maDonHang}>
      <td><strong>{item.maDonHang}</strong><small>{date(item.ngayDat)}</small></td>
      <td>{item.ve_ghes_count} vé · {item.chi_tiet_combo_don_hangs_sum_so_luong || 0} combo</td>
      <td className="cm-money">{money(item.tongTien)}</td>
      <td><span className={'cm-badge ' + (item.trangThai === 'DA_THANH_TOAN' ? 'cm-green' : 'cm-gray')}>{labels[item.trangThai] || item.trangThai}</span></td>
    </tr>)}</tbody></table></div> : <div className="cm-empty"><h3>Chưa có giao dịch</h3><p>Khách hàng này chưa có đơn hàng.</p></div>}
    <div className="cm-pagination"><button type="button" disabled={page === 1} onClick={() => setPage(page - 1)}>Trước</button><span>Trang {page} / {data.last_page}</span><button type="button" disabled={page >= data.last_page} onClick={() => setPage(page + 1)}>Sau</button></div>
  </>;
}
