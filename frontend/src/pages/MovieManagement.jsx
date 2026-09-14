
import { useEffect, useRef, useState } from "react";
import {
  deleteMovie,
  loadMovies,
  saveMovie,
} from "../services/movieManagement";
import "./MovieManagement.css";

const statuses = {
  SAP_CHIEU: "Sắp chiếu",
  DANG_CHIEU: "Đang chiếu",
  DA_CHIEU: "Đã chiếu",
};

const emptyMovie = {
  maPhim: "",
  tenPhim: "",
  theLoai: "",
  thoiLuong: "",
  daoDien: "",
  dienVien: "",
  ngayKhoiChieu: "",
  ngayKetThuc: "",
  moTa: "",
  hinhAnh: "",
  trailer: "",
  trangThai: "SAP_CHIEU",
};

const fields = [
  ["maPhim", "Mã phim", "text", 50],
  ["tenPhim", "Tên phim", "text", 255],
  ["theLoai", "Thể loại", "text", 255],
  ["thoiLuong", "Thời lượng (phút)", "number"],
  ["daoDien", "Đạo diễn", "text", 255],
  ["dienVien", "Diễn viên", "text"],
  ["ngayKhoiChieu", "Ngày khởi chiếu", "date"],
  ["ngayKetThuc", "Ngày kết thúc", "date"],
  ["hinhAnh", "Link ảnh poster", "url", 500],
  ["trailer", "Đường dẫn trailer", "url"],
];

function MoviePoster({ src, title }) {
  const [failed, setFailed] = useState(false);

  if (!src || failed) {
    return (
      <div className="management-movie-poster poster-placeholder">
        {src ? "Không tải được ảnh" : "Chưa có ảnh"}
      </div>
    );
  }

  return (
    <img
      className="management-movie-poster"
      src={src}
      alt={`Poster ${title}`}
      loading="lazy"
      onError={() => setFailed(true)}
    />
  );
}

export default function MovieManagement() {
  const [movies, setMovies] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [message, setMessage] = useState("");

  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("");
  const [page, setPage] = useState(1);

  const [form, setForm] = useState(null);
  const [editing, setEditing] = useState(false);
  const [formError, setFormError] = useState("");

  const [busy, setBusy] = useState(false);
  const [reload, setReload] = useState(0);

  const dialog = useRef(null);

  // =========================
  // LOAD DANH SÁCH PHIM
  // =========================
  useEffect(() => {
    const controller = new AbortController();

    loadMovies(controller.signal)
      .then(setMovies)
      .catch((err) => {
        if (!controller.signal.aborted) {
          setError(err.message);
        }
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setLoading(false);
        }
      });

    return () => controller.abort();
  }, [reload]);

  // =========================
  // TẢI LẠI
  // =========================
  const refresh = () => {
    setLoading(true);
    setError("");
    setReload((value) => value + 1);
  };

  // =========================
  // MỞ FORM THÊM / SỬA
  // =========================
  const openForm = (movie) => {
    setEditing(Boolean(movie));

    if (movie) {
      setForm(
        Object.fromEntries(
          Object.keys(emptyMovie).map((key) => [
            key,
            key.startsWith("ngay")
              ? (movie[key] || "").slice(0, 10)
              : movie[key] ?? "",
          ])
        )
      );
    } else {
      setForm({ ...emptyMovie });
    }

    setFormError("");
    setMessage("");

    dialog.current.showModal();
  };

  // =========================
  // ĐÓNG FORM
  // =========================
  const closeForm = () => {
    if (dialog.current?.open) {
      dialog.current.close();
    }

    setForm(null);
    setFormError("");
  };

  // =========================
  // SUBMIT THÊM / SỬA
  // =========================
  const submit = async (event) => {
    event.preventDefault();

    if (busy) return;

    setFormError("");

    const data = Object.fromEntries(
      Object.entries(form).map(([key, value]) => [
        key,
        typeof value === "string" ? value.trim() : value,
      ])
    );

    // =========================
    // VALIDATE
    // =========================

    if (!data.maPhim || !data.tenPhim) {
      setFormError("Vui lòng nhập mã phim và tên phim.");
      return;
    }

    if (
      data.ngayKetThuc &&
      data.ngayKhoiChieu &&
      data.ngayKetThuc < data.ngayKhoiChieu
    ) {
      setFormError(
        "Ngày kết thúc phải từ ngày khởi chiếu trở đi."
      );
      return;
    }

    // Kiểm tra link ảnh
    if (data.hinhAnh) {
      try {
        new URL(data.hinhAnh);
      } catch {
        setFormError(
          "Link ảnh poster không hợp lệ. Vui lòng nhập URL đầy đủ."
        );
        return;
      }
    }

    // Kiểm tra link trailer
    if (data.trailer) {
      try {
        new URL(data.trailer);
      } catch {
        setFormError(
          "Đường dẫn trailer không hợp lệ."
        );
        return;
      }
    }

    // Chuyển chuỗi rỗng thành null
    for (const key of Object.keys(data)) {
      if (data[key] === "") {
        data[key] = null;
      }
    }

    if (data.thoiLuong !== null) {
      data.thoiLuong = Number(data.thoiLuong);
    }

    setBusy(true);

    try {
      // Chỉ gửi data.
      // Không còn posterFile vì ảnh chỉ dùng link.
      await saveMovie(data, editing);

      closeForm();

      setMessage(
        editing
          ? "Cập nhật phim thành công."
          : "Thêm phim thành công."
      );

      refresh();
    } catch (err) {
      setFormError(err.message);
    } finally {
      setBusy(false);
    }
  };

  // =========================
  // XÓA PHIM
  // =========================
  const remove = async (movie) => {
    if (
      busy ||
      !window.confirm(
        `Xóa phim “${movie.tenPhim}”? Thao tác này không thể hoàn tác.`
      )
    ) {
      return;
    }

    setBusy(true);
    setError("");
    setMessage("");

    try {
      await deleteMovie(movie.maPhim);

      setMessage("Xóa phim thành công.");

      refresh();
    } catch (err) {
      setError(err.message);
    } finally {
      setBusy(false);
    }
  };

  // =========================
  // LỌC PHIM
  // =========================
  const keyword = search
    .trim()
    .toLocaleLowerCase("vi");

  const filtered = movies.filter(
    (movie) =>
      (!status || movie.trangThai === status) &&
      [movie.maPhim, movie.tenPhim, movie.theLoai].some(
        (value) =>
          (value || "")
            .toLocaleLowerCase("vi")
            .includes(keyword)
      )
  );

  // =========================
  // PHÂN TRANG
  // =========================
  const pages = Math.max(
    1,
    Math.ceil(filtered.length / 10)
  );

  const currentPage = Math.min(page, pages);

  // =========================
  // GIAO DIỆN
  // =========================
  return (
    <section className="movie-management">

      {/* =========================
          HEADER
      ========================= */}
      <header className="movie-heading">
        <div>
          <h1>Quản lý phim</h1>

          <p>
            Quản lý thông tin, thời gian và trạng thái
            chiếu phim.
          </p>
        </div>

        <button
          onClick={() => openForm()}
          disabled={busy}
        >
          + Thêm phim
        </button>
      </header>


      {/* =========================
          FILTER
      ========================= */}
      <div className="movie-filters">

        <input
          aria-label="Tìm phim"
          placeholder="Tìm theo mã, tên phim hoặc thể loại..."
          value={search}
          onChange={(event) => {
            setSearch(event.target.value);
            setPage(1);
          }}
        />

        <select
          aria-label="Lọc trạng thái"
          value={status}
          onChange={(event) => {
            setStatus(event.target.value);
            setPage(1);
          }}
        >
          <option value="">
            Tất cả trạng thái
          </option>

          {Object.entries(statuses).map(
            ([value, label]) => (
              <option
                key={value}
                value={value}
              >
                {label}
              </option>
            )
          )}
        </select>

        <button
          onClick={refresh}
          disabled={loading || busy}
        >
          Tải lại
        </button>

      </div>


      {/* =========================
          MESSAGE
      ========================= */}
      {message && (
        <p
          className="movie-success"
          role="status"
        >
          {message}
        </p>
      )}

      {error && (
        <p
          className="movie-error"
          role="alert"
        >
          {error}
        </p>
      )}


      {/* =========================
          TABLE
      ========================= */}
      <div className="movie-table-wrap">

        <table>

          <thead>
            <tr>
              <th>Mã phim</th>
              <th>Ảnh phim</th>
              <th>Tên phim</th>
              <th>Thể loại</th>
              <th>Thời lượng</th>
              <th>Khởi chiếu</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>

          <tbody>

            {loading ? (
              <tr>
                <td colSpan="8">
                  Đang tải danh sách phim...
                </td>
              </tr>
            ) : !filtered.length ? (
              <tr>
                <td colSpan="8">
                  Không có phim phù hợp.
                </td>
              </tr>
            ) : (
              filtered
                .slice(
                  (currentPage - 1) * 10,
                  currentPage * 10
                )
                .map((movie) => (
                  <tr key={movie.maPhim}>

                    <td>
                      {movie.maPhim}
                    </td>

                    <td>
                      <MoviePoster
                        key={movie.hinhAnh}
                        src={movie.hinhAnh}
                        title={movie.tenPhim}
                      />
                    </td>

                    <td>
                      <strong>
                        {movie.tenPhim}
                      </strong>
                    </td>

                    <td>
                      {movie.theLoai || "—"}
                    </td>

                    <td>
                      {movie.thoiLuong
                        ? `${movie.thoiLuong} phút`
                        : "—"}
                    </td>

                    <td>
                      {movie.ngayKhoiChieu
                        ?.slice(0, 10)
                        .split("-")
                        .reverse()
                        .join("/") || "—"}
                    </td>

                    <td>
                      <span
                        className={`movie-status ${movie.trangThai}`}
                      >
                        {statuses[movie.trangThai] ||
                          movie.trangThai}
                      </span>
                    </td>

                    <td>
                      <div className="movie-actions">

                        <button
                          className="secondary"
                          onClick={() =>
                            openForm(movie)
                          }
                          disabled={busy}
                        >
                          Sửa
                        </button>

                        <button
                          onClick={() =>
                            remove(movie)
                          }
                          disabled={busy}
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


      {/* =========================
          PAGINATION
      ========================= */}
      <div className="movie-pagination">

        <span>
          {filtered.length} phim · Trang{" "}
          {currentPage}/{pages}
        </span>

        <button
          disabled={
            loading || currentPage === 1
          }
          onClick={() =>
            setPage(currentPage - 1)
          }
        >
          Trước
        </button>

        <button
          disabled={
            loading || currentPage === pages
          }
          onClick={() =>
            setPage(currentPage + 1)
          }
        >
          Sau
        </button>

      </div>


      {/* =========================
          FORM THÊM / SỬA
      ========================= */}
      <dialog
        ref={dialog}
        className="movie-dialog"
        aria-labelledby="movie-form-title"
        onCancel={(event) => {
          event.preventDefault();

          if (!busy) {
            closeForm();
          }
        }}
      >

        {form && (
          <form onSubmit={submit}>

            {/* HEADER FORM */}
            <header className="movie-heading">

              <h2 id="movie-form-title">
                {editing
                  ? "Sửa phim"
                  : "Thêm phim"}
              </h2>

              <button
                type="button"
                className="secondary"
                aria-label="Đóng"
                disabled={busy}
                onClick={closeForm}
              >
                ×
              </button>

            </header>


            {/* FORM GRID */}
            <div className="movie-form-grid">

              {fields.map(
                ([
                  name,
                  label,
                  type,
                  maxLength,
                ]) => (
                  <label key={name}>

                    {label}

                    {[
                      "maPhim",
                      "tenPhim",
                    ].includes(name) && " *"}

                    <input
                      name={name}
                      type={type}
                      maxLength={maxLength}
                      value={form[name] ?? ""}
                      disabled={
                        busy ||
                        (editing &&
                          name === "maPhim")
                      }
                      required={[
                        "maPhim",
                        "tenPhim",
                      ].includes(name)}
                      min={
                        type === "number"
                          ? 1
                          : name ===
                              "ngayKetThuc"
                            ? form.ngayKhoiChieu ||
                              undefined
                            : undefined
                      }
                      step={
                        type === "number"
                          ? 1
                          : undefined
                      }
                      placeholder={
                        name === "hinhAnh"
                          ? "https://example.com/poster.jpg"
                          : name === "trailer"
                          ? "https://youtube.com/..."
                          : ""
                      }
                      onChange={(event) => {
                        setForm({
                          ...form,
                          [name]:
                            event.target.value,
                        });

                        setFormError("");
                      }}
                    />

                    {/* HIỂN THỊ PREVIEW ẢNH */}
                    {name === "hinhAnh" &&
                      form.hinhAnh && (
                        <MoviePoster
                          src={form.hinhAnh}
                          title={
                            form.tenPhim ||
                            "phim"
                          }
                        />
                      )}

                  </label>
                )
              )}


              {/* TRẠNG THÁI */}
              <label>
                Trạng thái

                <select
                  value={form.trangThai}
                  disabled={busy}
                  onChange={(event) =>
                    setForm({
                      ...form,
                      trangThai:
                        event.target.value,
                    })
                  }
                >
                  {Object.entries(
                    statuses
                  ).map(
                    ([value, label]) => (
                      <option
                        key={value}
                        value={value}
                      >
                        {label}
                      </option>
                    )
                  )}
                </select>

              </label>


              {/* MÔ TẢ */}
              <label className="movie-description">

                Mô tả

                <textarea
                  rows="4"
                  value={form.moTa ?? ""}
                  disabled={busy}
                  onChange={(event) => {
                    setForm({
                      ...form,
                      moTa: event.target.value,
                    });

                    setFormError("");
                  }}
                />

              </label>

            </div>


            {/* ERROR */}
            {formError && (
              <p
                className="movie-error"
                role="alert"
              >
                {formError}
              </p>
            )}


            {/* FOOTER */}
            <footer className="movie-actions">

              <button
                type="button"
                className="secondary"
                disabled={busy}
                onClick={closeForm}
              >
                Hủy
              </button>

              <button disabled={busy}>
                {busy
                  ? "Đang lưu..."
                  : editing
                  ? "Cập nhật"
                  : "Thêm phim"}
              </button>

            </footer>

          </form>
        )}

      </dialog>

    </section>
  );
}
