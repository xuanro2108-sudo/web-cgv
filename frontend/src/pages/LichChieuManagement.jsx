import {
  useEffect,
  useState,
} from "react";

import "./LichChieuManagement.css";

const API =
  "http://127.0.0.1:8000/api";

const emptyForm = {
  maLichChieu: "",
  maPhim: "",
  maPhong: "",
  ngayChieu: "",
  gioBatDau: "",
  gioKetThuc: "",
  giaVeCoBan: "",
};

function LichChieuManagement() {
  const [items, setItems] =
    useState([]);

  const [phims, setPhims] =
    useState([]);

  const [phongs, setPhongs] =
    useState([]);

  const [form, setForm] =
    useState(emptyForm);

  const [editingId, setEditingId] =
    useState(null);

  const [keyword, setKeyword] =
    useState("");

  const [ngayLoc, setNgayLoc] =
    useState("");

  const [trangThaiLoc, setTrangThaiLoc] =
    useState("");

  const [page, setPage] =
    useState(1);

  const [lastPage, setLastPage] =
    useState(1);

  const [loading, setLoading] =
    useState(false);

  const [error, setError] =
    useState("");

  const [message, setMessage] =
    useState("");

  const token =
    localStorage.getItem("token");

  const authHeaders = {
    Accept: "application/json",
    Authorization:
      `Bearer ${token}`,
  };


  const formatMoney = (value) =>
    Number(
      value || 0
    ).toLocaleString(
      "vi-VN"
    ) + " đ";


  const formatDate = (value) => {
    if (!value) return "";

    const date =
      String(value).substring(
        0,
        10
      );

    return new Date(
      `${date}T00:00:00`
    ).toLocaleDateString(
      "vi-VN"
    );
  };


  const formatTime = (value) => {
    if (!value) return "";

    if (
      String(value).includes("T")
    ) {
      return new Date(
        value
      ).toLocaleTimeString(
        "vi-VN",
        {
          hour: "2-digit",
          minute: "2-digit",
        }
      );
    }

    return String(
      value
    ).substring(0, 5);
  };


  const getErrorMessage =
    (result) => {

      if (result?.errors) {
        const first =
          Object.values(
            result.errors
          )[0];

        if (
          Array.isArray(first)
        ) {
          return first[0];
        }
      }

      return (
        result?.message ||
        "Đã xảy ra lỗi."
      );
    };


  const loadOptions =
    async () => {

      try {
        const [
          phimResponse,
          phongResponse,
        ] =
          await Promise.all([
            fetch(
              `${API}/phims`
            ),

            fetch(
              `${API}/phong-chieus`
            ),
          ]);

        const phimResult =
          await phimResponse.json();

        const phongResult =
          await phongResponse.json();

        setPhims(
          phimResult.data || []
        );

        setPhongs(
          phongResult.data || []
        );

      } catch (err) {
        console.error(
          err
        );
      }
    };


  const loadData =
    async (
      targetPage = page
    ) => {

      setLoading(true);

      setError("");

      try {
        const params =
          new URLSearchParams({
            page:
              String(
                targetPage
              ),
          });

        if (
          keyword.trim()
        ) {
          params.append(
            "q",
            keyword.trim()
          );
        }

        if (ngayLoc) {
          params.append(
            "ngayChieu",
            ngayLoc
          );
        }

        if (
          trangThaiLoc
        ) {
          params.append(
            "trangThai",
            trangThaiLoc
          );
        }

        const response =
          await fetch(
            `${API}/quan-ly/lich-chieus?${params.toString()}`,
            {
              headers:
                authHeaders,
            }
          );

        const result =
          await response.json();

        if (!response.ok) {
          throw new Error(
            getErrorMessage(
              result
            )
          );
        }

        setItems(
          result.data || []
        );

        setPage(
          result.current_page || 1
        );

        setLastPage(
          result.last_page || 1
        );

      } catch (err) {
        setError(
          err.message
        );
      } finally {
        setLoading(false);
      }
    };


  useEffect(() => {
    loadOptions();
    loadData(1);
  }, []);


  const handleChange =
    (event) => {

      const {
        name,
        value,
      } = event.target;

      setForm(
        (old) => ({
          ...old,
          [name]: value,
        })
      );
    };


  const resetForm = () => {
    setEditingId(null);

    setForm(
      emptyForm
    );

    setError("");
  };


  const validateForm =
    () => {

      if (
        !form.maLichChieu ||
        !form.maPhim ||
        !form.maPhong ||
        !form.ngayChieu ||
        !form.gioBatDau ||
        !form.gioKetThuc ||
        !form.giaVeCoBan
      ) {
        return (
          "Vui lòng nhập đầy đủ thông tin."
        );
      }

      if (
        form.gioKetThuc <=
        form.gioBatDau
      ) {
        return (
          "Giờ kết thúc phải sau giờ bắt đầu."
        );
      }

      if (
        Number(
          form.giaVeCoBan
        ) < 0
      ) {
        return (
          "Giá vé không hợp lệ."
        );
      }

      return "";
    };


  const handleSubmit =
    async (event) => {

      event.preventDefault();

      setMessage("");

      setError("");

      const validation =
        validateForm();

      if (validation) {
        setError(
          validation
        );

        return;
      }

      setLoading(true);

      try {
        const body = {
          maPhim:
            form.maPhim,

          maPhong:
            form.maPhong,

          ngayChieu:
            form.ngayChieu,

          gioBatDau:
            form.gioBatDau,

          gioKetThuc:
            form.gioKetThuc,

          giaVeCoBan:
            Number(
              form.giaVeCoBan
            ),
        };

        if (!editingId) {
          body.maLichChieu =
            form.maLichChieu;
        }

        const response =
          await fetch(
            editingId
              ? `${API}/lich-chieus/${editingId}`
              : `${API}/lich-chieus`,
            {
              method:
                editingId
                  ? "PUT"
                  : "POST",

              headers: {
                ...authHeaders,

                "Content-Type":
                  "application/json",
              },

              body:
                JSON.stringify(
                  body
                ),
            }
          );

        const result =
          await response.json();

        if (!response.ok) {
          throw new Error(
            getErrorMessage(
              result
            )
          );
        }

        setMessage(
          result.message ||
          (
            editingId
              ? "Cập nhật lịch chiếu thành công."
              : "Thêm lịch chiếu thành công."
          )
        );

        resetForm();

        await loadData(1);

      } catch (err) {
        setError(
          err.message
        );
      } finally {
        setLoading(false);
      }
    };


  const handleEdit =
    (item) => {

      setMessage("");

      setError("");

      setEditingId(
        item.maLichChieu
      );

      setForm({
        maLichChieu:
          item.maLichChieu,

        maPhim:
          item.maPhim,

        maPhong:
          item.maPhong,

        ngayChieu:
          String(
            item.ngayChieu
          ).substring(
            0,
            10
          ),

        gioBatDau:
          formatTime(
            item.gioBatDau
          ),

        gioKetThuc:
          formatTime(
            item.gioKetThuc
          ),

        giaVeCoBan:
          item.giaVeCoBan,
      });

      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    };


  const handleCancel =
    async (item) => {

      setError("");

      setMessage("");

      const confirmCancel =
        window.confirm(
          `Bạn có chắc muốn hủy lịch chiếu ${item.maLichChieu}?`
        );

      if (!confirmCancel) {
        return;
      }

      try {
        const response =
          await fetch(
            `${API}/lich-chieus/${item.maLichChieu}`,
            {
              method:
                "DELETE",

              headers:
                authHeaders,
            }
          );

        const result =
          await response.json();

        if (!response.ok) {
          throw new Error(
            getErrorMessage(
              result
            )
          );
        }

        setMessage(
          result.message ||
          "Hủy lịch chiếu thành công."
        );

        await loadData(
          page
        );

      } catch (err) {
        setError(
          err.message
        );
      }
    };


  const handleSearch =
    () => {
      loadData(1);
    };


  const clearFilter =
    () => {

      setKeyword("");

      setNgayLoc("");

      setTrangThaiLoc("");

      setTimeout(
        () => {
          loadData(1);
        },
        0
      );
    };


  return (
    <div className="schedule-page">


      <div className="schedule-header">

        <h1>
          Quản lý lịch chiếu
        </h1>

        <p>
          Thêm, cập nhật và hủy
          lịch chiếu tại CGV
          AEON Mall Hà Đông.
        </p>

      </div>


      <form
        className="schedule-form"
        onSubmit={
          handleSubmit
        }
      >

        <div className="schedule-form-title">

          <h2>
            {editingId
              ? "Cập nhật lịch chiếu"
              : "Thêm lịch chiếu mới"}
          </h2>

          {editingId && (
            <span>
              {editingId}
            </span>
          )}

        </div>


        <div className="schedule-form-grid">


          <div className="schedule-field">

            <label>
              Mã lịch chiếu
            </label>

            <input
              name="maLichChieu"
              value={
                form.maLichChieu
              }
              onChange={
                handleChange
              }
              disabled={
                Boolean(
                  editingId
                )
              }
              placeholder="VD: LC001"
            />

          </div>


          <div className="schedule-field">

            <label>
              Phim
            </label>

            <select
              name="maPhim"
              value={
                form.maPhim
              }
              onChange={
                handleChange
              }
            >

              <option value="">
                Chọn phim
              </option>

              {phims.map(
                (item) => (

                  <option
                    key={
                      item.maPhim
                    }
                    value={
                      item.maPhim
                    }
                  >
                    {item.tenPhim}
                  </option>

                )
              )}

            </select>

          </div>


          <div className="schedule-field">

            <label>
              Phòng chiếu
            </label>

            <select
              name="maPhong"
              value={
                form.maPhong
              }
              onChange={
                handleChange
              }
            >

              <option value="">
                Chọn phòng
              </option>

              {phongs.map(
                (item) => (

                  <option
                    key={
                      item.maPhong
                    }
                    value={
                      item.maPhong
                    }
                  >
                    {item.tenPhong}
                  </option>

                )
              )}

            </select>

          </div>


          <div className="schedule-field">

            <label>
              Ngày chiếu
            </label>

            <input
              type="date"
              name="ngayChieu"
              value={
                form.ngayChieu
              }
              onChange={
                handleChange
              }
            />

          </div>


          <div className="schedule-field">

            <label>
              Giờ bắt đầu
            </label>

            <input
              type="time"
              name="gioBatDau"
              value={
                form.gioBatDau
              }
              onChange={
                handleChange
              }
            />

          </div>


          <div className="schedule-field">

            <label>
              Giờ kết thúc
            </label>

            <input
              type="time"
              name="gioKetThuc"
              value={
                form.gioKetThuc
              }
              onChange={
                handleChange
              }
            />

          </div>


          <div className="schedule-field">

            <label>
              Giá vé cơ bản
            </label>

            <input
              type="number"
              min="0"
              step="1000"
              name="giaVeCoBan"
              value={
                form.giaVeCoBan
              }
              onChange={
                handleChange
              }
              placeholder="VD: 90000"
            />

          </div>

        </div>


        <div className="schedule-form-actions">

          <button
            type="submit"
            className="schedule-save"
            disabled={
              loading
            }
          >
            {editingId
              ? "Lưu thay đổi"
              : "Lưu lịch chiếu"}
          </button>


          {editingId && (

            <button
              type="button"
              className="schedule-cancel-edit"
              onClick={
                resetForm
              }
            >
              Hủy chỉnh sửa
            </button>

          )}

        </div>

      </form>


      {message && (

        <div className="schedule-success">
          {message}
        </div>

      )}


      {error && (

        <div className="schedule-error">
          {error}
        </div>

      )}


      <div className="schedule-filter">

        <input
          value={
            keyword
          }
          onChange={(e) =>
            setKeyword(
              e.target.value
            )
          }
          placeholder="Tìm mã, phim hoặc phòng..."
        />


        <input
          type="date"
          value={
            ngayLoc
          }
          onChange={(e) =>
            setNgayLoc(
              e.target.value
            )
          }
        />


        <select
          value={
            trangThaiLoc
          }
          onChange={(e) =>
            setTrangThaiLoc(
              e.target.value
            )
          }
        >

          <option value="">
            Tất cả trạng thái
          </option>

          <option value="HOAT_DONG">
            Hoạt động
          </option>

          <option value="NGUNG_HOAT_DONG">
            Đã hủy
          </option>

        </select>


        <button
          type="button"
          className="filter-search"
          onClick={
            handleSearch
          }
        >
          Tìm kiếm
        </button>


        <button
          type="button"
          className="filter-clear"
          onClick={
            clearFilter
          }
        >
          Xóa lọc
        </button>

      </div>


      <div className="schedule-table-box">

        <div className="schedule-table-title">

          <h2>
            Danh sách lịch chiếu
          </h2>

          {loading && (
            <span>
              Đang tải...
            </span>
          )}

        </div>


        <div className="schedule-table-wrapper">

          <table className="schedule-table">

            <thead>

              <tr>
                <th>Mã lịch</th>
                <th>Phim</th>
                <th>Phòng</th>
                <th>Ngày</th>
                <th>Giờ chiếu</th>
                <th>Giá vé</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
              </tr>

            </thead>


            <tbody>

              {!loading &&
              items.length === 0 ? (

                <tr>

                  <td
                    colSpan="8"
                    className="schedule-empty"
                  >
                    Không có lịch chiếu.
                  </td>

                </tr>

              ) : (

                items.map(
                  (item) => {

                    const active =
                      item.trangThai ===
                      "HOAT_DONG";

                    return (

                      <tr
                        key={
                          item.maLichChieu
                        }
                      >

                        <td>
                          {item.maLichChieu}
                        </td>


                        <td>
                          {item.phim
                            ?.tenPhim ||
                            item.maPhim}
                        </td>


                        <td>
                          {item.phong_chieu
                            ?.tenPhong ||
                            item.phongChieu
                              ?.tenPhong ||
                            item.maPhong}
                        </td>


                        <td>
                          {formatDate(
                            item.ngayChieu
                          )}
                        </td>


                        <td>
                          {formatTime(
                            item.gioBatDau
                          )}
                          {" - "}
                          {formatTime(
                            item.gioKetThuc
                          )}
                        </td>


                        <td>
                          {formatMoney(
                            item.giaVeCoBan
                          )}
                        </td>


                        <td>

                          <span
                            className={
                              active
                                ? "schedule-status active"
                                : "schedule-status cancelled"
                            }
                          >
                            {active
                              ? "Hoạt động"
                              : "Đã hủy"}
                          </span>

                        </td>


                        <td>

                          {active ? (

                            <div className="schedule-actions">

                              <button
                                type="button"
                                className="schedule-edit"
                                onClick={() =>
                                  handleEdit(
                                    item
                                  )
                                }
                              >
                                Cập nhật
                              </button>


                              <button
                                type="button"
                                className="schedule-cancel"
                                onClick={() =>
                                  handleCancel(
                                    item
                                  )
                                }
                              >
                                Hủy
                              </button>

                            </div>

                          ) : (

                            <span className="schedule-no-action">
                              Đã hủy
                            </span>

                          )}

                        </td>

                      </tr>
                    );
                  }
                )
              )}

            </tbody>

          </table>

        </div>


        <div className="schedule-pagination">

          <button
            type="button"
            disabled={
              page <= 1 ||
              loading
            }
            onClick={() =>
              loadData(
                page - 1
              )
            }
          >
            ← Trước
          </button>


          <span>
            Trang {page}
            {" / "}
            {lastPage}
          </span>


          <button
            type="button"
            disabled={
              page >= lastPage ||
              loading
            }
            onClick={() =>
              loadData(
                page + 1
              )
            }
          >
            Sau →
          </button>

        </div>

      </div>

    </div>
  );
}

export default LichChieuManagement;