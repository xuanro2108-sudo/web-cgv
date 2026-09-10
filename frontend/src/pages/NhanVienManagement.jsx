import { useEffect, useState } from "react";
import "./NhanVienManagement.css";

const API = "http://127.0.0.1:8000/api/quan-ly/nhan-viens";

function NhanVienManagement() {
  const token = localStorage.getItem("token");

  const [nhanViens, setNhanViens] = useState([]);
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [showForm, setShowForm] = useState(false);
  const [editingMaNV, setEditingMaNV] = useState(null);

  const [formData, setFormData] = useState({
    hoTen: "",
    sdt: "",
    email: "",
    chucVu: "NHAN_VIEN",
    ngayVaoLam: "",
    matKhau: "",
    trangThai: "DANG_LAM",
  });

  const now = new Date();
  const today =
    `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, "0")}-${String(now.getDate()).padStart(2, "0")}`;

  const fetchNhanViens = async (keyword = "") => {
    setLoading(true);
    setError("");

    try {
      const url = keyword.trim()
        ? `${API}?search=${encodeURIComponent(keyword.trim())}`
        : API;

      const res = await fetch(url, {
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
      });

      const data = await res.json();

      if (!res.ok) throw new Error(data.message || "Không thể tải danh sách.");

      setNhanViens(data.data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchNhanViens();
  }, []);

  const handleChange = (e) => {
    const { name, value } = e.target;

    if (name === "sdt") {
      setFormData({
        ...formData,
        sdt: value.replace(/\D/g, "").slice(0, 10),
      });
      return;
    }

    setFormData({ ...formData, [name]: value });
  };

  const handleAdd = () => {
    setEditingMaNV(null);
    setMessage("");
    setError("");

    setFormData({
      hoTen: "",
      sdt: "",
      email: "",
      chucVu: "NHAN_VIEN",
      ngayVaoLam: "",
      matKhau: "",
      trangThai: "DANG_LAM",
    });

    setShowForm(true);
  };

  const handleEdit = (nv) => {
    setEditingMaNV(nv.maNV);
    setMessage("");
    setError("");

    setFormData({
      hoTen: nv.hoTen || "",
      sdt: nv.sdt || "",
      email: nv.email || "",
      chucVu: nv.chucVu || "NHAN_VIEN",
      ngayVaoLam: nv.ngayVaoLam?.substring(0, 10) || "",
      matKhau: "",
      trangThai: nv.trangThai || "DANG_LAM",
    });

    setShowForm(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError("");
    setMessage("");

    if (!/^0[0-9]{9}$/.test(formData.sdt)) {
      setError("Số điện thoại phải đủ 10 số và bắt đầu bằng 0.");
      return;
    }

    if (!/^[A-Za-z0-9._%+-]+@gmail\.com$/i.test(formData.email.trim())) {
      setError("Email phải có định dạng @gmail.com.");
      return;
    }

    if (formData.ngayVaoLam > today) {
      setError("Ngày vào làm không được ở tương lai.");
      return;
    }

    if (!editingMaNV && formData.matKhau.length < 6) {
      setError("Mật khẩu phải có ít nhất 6 ký tự.");
      return;
    }

    setSubmitting(true);

    try {
      const isEdit = Boolean(editingMaNV);
      const url = isEdit ? `${API}/${editingMaNV}` : API;

      const body = {
        hoTen: formData.hoTen.trim(),
        sdt: formData.sdt,
        email: formData.email.trim().toLowerCase(),
        chucVu: formData.chucVu,
        ngayVaoLam: formData.ngayVaoLam,
        ...(isEdit
          ? { trangThai: formData.trangThai }
          : { matKhau: formData.matKhau }),
      };

      const res = await fetch(url, {
        method: isEdit ? "PUT" : "POST",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(body),
      });

      const data = await res.json();

      if (!res.ok) {
        const firstError = data.errors
          ? Object.values(data.errors)[0]
          : null;

        throw new Error(
          firstError
            ? Array.isArray(firstError)
              ? firstError[0]
              : firstError
            : data.message || "Thao tác không thành công."
        );
      }

      setMessage(isEdit ? "Cập nhật thành công." : "Thêm nhân viên thành công.");
      setShowForm(false);
      setEditingMaNV(null);
      await fetchNhanViens(search);
    } catch (err) {
      setError(err.message);
    } finally {
      setSubmitting(false);
    }
  };

  const handleDelete = async (nv) => {
    if (!window.confirm(`Bạn có chắc muốn xóa "${nv.hoTen}"?`)) return;

    setError("");
    setMessage("");

    try {
      const res = await fetch(`${API}/${nv.maNV}`, {
        method: "DELETE",
        headers: {
          Accept: "application/json",
          Authorization: `Bearer ${token}`,
        },
      });

      const data = await res.json();

      if (!res.ok) throw new Error(data.message || "Xóa không thành công.");

      setMessage("Xóa nhân viên thành công.");
      await fetchNhanViens(search);
    } catch (err) {
      setError(err.message);
    }
  };

  return (
    <section className="employee-management">
      <div className="employee-header">
        <div>
          <h1>Quản lý nhân viên</h1>
          <p>Xem, tìm kiếm, thêm, sửa và xóa nhân viên.</p>
        </div>

        <button className="employee-add-button" onClick={handleAdd}>
          + Thêm nhân viên
        </button>
      </div>

      <form
        className="employee-search"
        onSubmit={(e) => {
          e.preventDefault();
          fetchNhanViens(search);
        }}
      >
        <input
          placeholder="Tìm theo mã, họ tên, SĐT hoặc email..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />

        <button type="submit">Tìm kiếm</button>

        {search && (
          <button
            type="button"
            className="employee-clear-button"
            onClick={() => {
              setSearch("");
              fetchNhanViens("");
            }}
          >
            Xóa tìm kiếm
          </button>
        )}
      </form>

      {message && <div className="employee-success">{message}</div>}
      {error && <div className="employee-error">{error}</div>}

      <div className="employee-table-wrapper">
        <table className="employee-table">
          <thead>
            <tr>
              <th>Mã NV</th>
              <th>Họ tên</th>
              <th>SĐT</th>
              <th>Email</th>
              <th>Chức vụ</th>
              <th>Ngày vào làm</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>

          <tbody>
            {loading ? (
              <tr>
                <td colSpan="8">Đang tải...</td>
              </tr>
            ) : nhanViens.length === 0 ? (
              <tr>
                <td colSpan="8">Không có nhân viên.</td>
              </tr>
            ) : (
              nhanViens.map((nv) => (
                <tr key={nv.maNV}>
                  <td>{nv.maNV}</td>
                  <td>{nv.hoTen}</td>
                  <td>{nv.sdt}</td>
                  <td>{nv.email}</td>
                  <td>{nv.chucVu === "QUAN_LY" ? "Quản lý" : "Nhân viên"}</td>
                  <td>{nv.ngayVaoLam?.substring(0, 10)}</td>

                  <td>
                    <span
                      className={
                        nv.trangThai === "DANG_LAM"
                          ? "employee-status working"
                          : "employee-status stopped"
                      }
                    >
                      {nv.trangThai === "DANG_LAM" ? "Đang làm" : "Nghỉ việc"}
                    </span>
                  </td>

                  <td>
                    <div className="employee-actions">
                      <button
                        className="employee-edit-button"
                        onClick={() => handleEdit(nv)}
                      >
                        Sửa
                      </button>

                      <button
                        className="employee-delete-button"
                        disabled={nv.trangThai === "NGHI_VIEC"}
                        onClick={() => handleDelete(nv)}
                      >
                        Xóa
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {showForm && (
        <div className="employee-modal-overlay">
          <div className="employee-modal">
            <div className="employee-modal-header">
              <h2>{editingMaNV ? "Sửa nhân viên" : "Thêm nhân viên"}</h2>

              <button
                className="employee-close"
                onClick={() => setShowForm(false)}
              >
                ×
              </button>
            </div>

            <form className="employee-form" onSubmit={handleSubmit}>
              <label>Họ và tên</label>
              <input
                name="hoTen"
                value={formData.hoTen}
                onChange={handleChange}
                required
              />

              <label>Số điện thoại</label>
              <input
                name="sdt"
                value={formData.sdt}
                onChange={handleChange}
                maxLength="10"
                pattern="0[0-9]{9}"
                placeholder="0987654321"
                required
              />

              <label>Email</label>
              <input
                type="email"
                name="email"
                value={formData.email}
                onChange={handleChange}
                pattern="[A-Za-z0-9._%+-]+@gmail\.com"
                placeholder="nhanvien@gmail.com"
                required
              />

              <label>Chức vụ</label>
              <select
                name="chucVu"
                value={formData.chucVu}
                onChange={handleChange}
              >
                <option value="NHAN_VIEN">Nhân viên</option>
                <option value="QUAN_LY">Quản lý</option>
              </select>

              <label>Ngày vào làm</label>
              <input
                type="date"
                name="ngayVaoLam"
                value={formData.ngayVaoLam}
                onChange={handleChange}
                max={today}
                required
              />

              {!editingMaNV && (
                <>
                  <label>Mật khẩu</label>
                  <input
                    type="password"
                    name="matKhau"
                    value={formData.matKhau}
                    onChange={handleChange}
                    minLength="6"
                    required
                  />
                </>
              )}

              {editingMaNV && (
                <>
                  <label>Trạng thái</label>
                  <select
                    name="trangThai"
                    value={formData.trangThai}
                    onChange={handleChange}
                  >
                    <option value="DANG_LAM">Đang làm</option>
                    <option value="NGHI_VIEC">Nghỉ việc</option>
                  </select>
                </>
              )}

              <div className="employee-form-actions">
                <button
                  type="button"
                  className="employee-cancel-button"
                  onClick={() => setShowForm(false)}
                >
                  Hủy
                </button>

                <button
                  type="submit"
                  className="employee-save-button"
                  disabled={submitting}
                >
                  {submitting
                    ? "Đang xử lý..."
                    : editingMaNV
                    ? "Cập nhật"
                    : "Thêm nhân viên"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </section>
  );
}

export default NhanVienManagement;