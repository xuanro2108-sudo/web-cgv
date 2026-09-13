import { useEffect, useMemo, useState } from "react";
import "./PhongChieuManagement.css";

const API = "http://127.0.0.1:8000/api";

function PhongChieuManagement() {
  const token = localStorage.getItem("token");

  const [rooms, setRooms] = useState([]);
  const [loadingRooms, setLoadingRooms] = useState(true);

  const [keyword, setKeyword] = useState("");
  const [statusFilter, setStatusFilter] = useState("");

  const [selectedRoom, setSelectedRoom] = useState(null);
  const [seatMap, setSeatMap] = useState(null);
  const [loadingSeatMap, setLoadingSeatMap] = useState(false);

  const [message, setMessage] = useState("");
  const [error, setError] = useState("");

  const [editing, setEditing] = useState(false);
  const [savingRoom, setSavingRoom] = useState(false);
  const [roomForm, setRoomForm] = useState({
    tenPhong: "",
    trangThai: "HOAT_DONG",
  });

  const getErrorMessage = (data, fallback) => {
    if (data?.message) {
      return data.message;
    }

    if (data?.errors) {
      const text = Object.values(data.errors)
        .flat()
        .join(" ");

      if (text) {
        return text;
      }
    }

    return fallback;
  };

  // =====================================================
  // TẢI DANH SÁCH PHÒNG
  // =====================================================
  const loadRooms = async (searchKeyword = "") => {
    setLoadingRooms(true);
    setError("");

    try {
      const params = new URLSearchParams();

      if (searchKeyword.trim()) {
        params.set("keyword", searchKeyword.trim());
      }

      const query = params.toString();
      const response = await fetch(
        `${API}/phong-chieus${query ? `?${query}` : ""}`,
        {
          headers: {
            Accept: "application/json",
          },
        }
      );

      const data = await response.json();

      if (!response.ok) {
        throw new Error(
          getErrorMessage(
            data,
            "Không thể tải danh sách phòng chiếu."
          )
        );
      }

      setRooms(Array.isArray(data.data) ? data.data : []);
    } catch (err) {
      console.error(err);
      setRooms([]);
      setError(
        err.message ||
          "Không thể tải danh sách phòng chiếu."
      );
    } finally {
      setLoadingRooms(false);
    }
  };

  useEffect(() => {
    loadRooms();
  }, []);

  // =====================================================
  // LỌC PHÒNG Ở FRONTEND
  // =====================================================
  const visibleRooms = useMemo(() => {
    return rooms.filter((room) => {
      if (
        statusFilter &&
        room.trangThai !== statusFilter
      ) {
        return false;
      }

      return true;
    });
  }, [rooms, statusFilter]);

  const handleSearch = async (event) => {
    event.preventDefault();
    await loadRooms(keyword);
  };

  const handleReset = async () => {
    setKeyword("");
    setStatusFilter("");
    await loadRooms("");
  };

  // =====================================================
  // LẤY SƠ ĐỒ GHẾ CỦA PHÒNG
  // =====================================================
  const selectRoom = async (room) => {
    setSelectedRoom(room);
    setSeatMap(null);
    setMessage("");
    setError("");
    setLoadingSeatMap(true);

    try {
      const roomResponse = await fetch(
        `${API}/phong-chieus/${encodeURIComponent(
          room.maPhong
        )}`,
        {
          headers: {
            Accept: "application/json",
          },
        }
      );

      const roomJson = await roomResponse.json();

      if (!roomResponse.ok) {
        throw new Error(
          getErrorMessage(
            roomJson,
            "Không thể tải thông tin phòng chiếu."
          )
        );
      }

      const roomData =
        roomJson.data || roomJson;

      setSelectedRoom(roomData);

      const soDo =
        roomData.so_do_ghe ||
        roomData.soDoGhe ||
        room.so_do_ghe ||
        room.soDoGhe;

      if (!soDo?.maSoDo) {
        setSeatMap(null);
        return;
      }

      const seatResponse = await fetch(
        `${API}/so-do-ghes/${encodeURIComponent(
          soDo.maSoDo
        )}`,
        {
          headers: {
            Accept: "application/json",
          },
        }
      );

      const seatJson = await seatResponse.json();

      if (!seatResponse.ok) {
        throw new Error(
          getErrorMessage(
            seatJson,
            "Không thể tải sơ đồ ghế."
          )
        );
      }

      setSeatMap(
        seatJson.data || seatJson
      );
    } catch (err) {
      console.error(err);
      setError(
        err.message ||
          "Không thể tải sơ đồ ghế."
      );
    } finally {
      setLoadingSeatMap(false);
    }
  };

  // =====================================================
  // GOM GHẾ THEO HÀNG + GHÉP 2 GHẾ ĐÔI THÀNH 1
  //
  // Tự động khớp đúng 4 phòng:
  // P001: A-H, H là ghế đôi
  // P002: A-J, J là ghế đôi
  // P003: A-L, L là ghế đôi
  // P004: A-F, F là ghế đôi
  // =====================================================
  const seatGroups = useMemo(() => {
    const seats = Array.isArray(
      seatMap?.ghes
    )
      ? [...seatMap.ghes]
      : [];

    seats.sort((a, b) => {
      const rowCompare =
        String(a.hang).localeCompare(
          String(b.hang),
          "vi",
          {
            numeric: true,
          }
        );

      if (rowCompare !== 0) {
        return rowCompare;
      }

      return Number(a.cot) - Number(b.cot);
    });

    const rows = {};

    seats.forEach((seat) => {
      const row = seat.hang || "?";

      if (!rows[row]) {
        rows[row] = [];
      }

      rows[row].push(seat);
    });

    return Object.entries(rows).map(
      ([row, rowSeats]) => {
        const groups = [];

        rowSeats.forEach((seat) => {
          const previous =
            groups[groups.length - 1];

          const isPair =
            seat.loaiGhe === "DOI" &&
            previous?.length === 1 &&
            previous[0].loaiGhe ===
              "DOI" &&
            Number(previous[0].cot) + 1 ===
              Number(seat.cot);

          if (isPair) {
            previous.push(seat);
          } else {
            groups.push([seat]);
          }
        });

        return [row, groups];
      }
    );
  }, [seatMap]);

  // =====================================================
  // THỐNG KÊ GHẾ CỦA PHÒNG
  // =====================================================
  const seatStats = useMemo(() => {
    const seats = Array.isArray(
      seatMap?.ghes
    )
      ? seatMap.ghes
      : [];

    return {
      total: seats.length,
      normal: seats.filter(
        (seat) =>
          seat.loaiGhe === "THUONG"
      ).length,
      vip: seats.filter(
        (seat) =>
          seat.loaiGhe === "VIP"
      ).length,
      couplePhysical: seats.filter(
        (seat) =>
          seat.loaiGhe === "DOI"
      ).length,
      couplePairs:
        seats.filter(
          (seat) =>
            seat.loaiGhe === "DOI"
        ).length / 2,
      locked: seats.filter(
        (seat) =>
          seat.trangThai === "KHOA"
      ).length,
    };
  }, [seatMap]);

  // =====================================================
  // KHÓA / MỞ GHẾ
  // GHẾ ĐÔI: LUÔN KHÓA/MỞ CẢ CẶP
  // =====================================================
  const handleToggleSeatGroup =
    async (seatGroup) => {
      if (
        !Array.isArray(seatGroup) ||
        seatGroup.length === 0
      ) {
        return;
      }

      const allLocked =
        seatGroup.every(
          (seat) =>
            seat.trangThai === "KHOA"
        );

      const nextStatus =
        allLocked
          ? "HOAT_DONG"
          : "KHOA";

      const seatLabel =
        seatGroup
          .map(
            (seat) =>
              `${seat.hang}${seat.cot}`
          )
          .join(" - ");

      const confirmed =
        window.confirm(
          nextStatus === "KHOA"
            ? `Khóa ghế ${seatLabel} do ghế đang hỏng/không sử dụng được?`
            : `Mở lại ghế ${seatLabel} sau khi đã xử lý xong?`
        );

      if (!confirmed) {
        return;
      }

      setError("");
      setMessage("");

      try {
        for (const seat of seatGroup) {
          const response = await fetch(
            `${API}/ghes/${encodeURIComponent(
              seat.maGhe
            )}`,
            {
              method: "PUT",
              headers: {
                Accept:
                  "application/json",
                "Content-Type":
                  "application/json",
                Authorization:
                  `Bearer ${token}`,
              },
              body: JSON.stringify({
                trangThai:
                  nextStatus,
              }),
            }
          );

          const data =
            await response.json();

          if (!response.ok) {
            throw new Error(
              getErrorMessage(
                data,
                "Không thể cập nhật trạng thái ghế."
              )
            );
          }
        }

        const changedIds =
          seatGroup.map(
            (seat) => seat.maGhe
          );

        setSeatMap((current) => {
          if (!current) {
            return current;
          }

          return {
            ...current,
            ghes: (
              current.ghes || []
            ).map((seat) =>
              changedIds.includes(
                seat.maGhe
              )
                ? {
                    ...seat,
                    trangThai:
                      nextStatus,
                  }
                : seat
            ),
          };
        });

        setMessage(
          nextStatus === "KHOA"
            ? `Đã khóa ghế ${seatLabel}.`
            : `Đã mở lại ghế ${seatLabel}.`
        );
      } catch (err) {
        console.error(err);
        setError(
          err.message ||
            "Không thể cập nhật trạng thái ghế."
        );
      }
    };

  // =====================================================
  // SỬA TÊN / TRẠNG THÁI PHÒNG
  // =====================================================
  const openEditRoom = () => {
    if (!selectedRoom) {
      return;
    }

    setRoomForm({
      tenPhong:
        selectedRoom.tenPhong || "",
      trangThai:
        selectedRoom.trangThai ||
        "HOAT_DONG",
    });

    setEditing(true);
    setError("");
    setMessage("");
  };

  const saveRoom = async (event) => {
    event.preventDefault();

    if (!selectedRoom) {
      return;
    }

    if (!roomForm.tenPhong.trim()) {
      setError(
        "Tên phòng không được để trống."
      );
      return;
    }

    setSavingRoom(true);
    setError("");
    setMessage("");

    try {
      const response = await fetch(
        `${API}/phong-chieus/${encodeURIComponent(
          selectedRoom.maPhong
        )}`,
        {
          method: "PUT",
          headers: {
            Accept:
              "application/json",
            "Content-Type":
              "application/json",
            Authorization:
              `Bearer ${token}`,
          },
          body: JSON.stringify({
            tenPhong:
              roomForm.tenPhong.trim(),
            trangThai:
              roomForm.trangThai,
          }),
        }
      );

      const data =
        await response.json();

      if (!response.ok) {
        throw new Error(
          getErrorMessage(
            data,
            "Cập nhật phòng chiếu thất bại."
          )
        );
      }

      const updated =
        data.data || selectedRoom;

      setSelectedRoom(updated);
      setEditing(false);

      setRooms((current) =>
        current.map((room) =>
          room.maPhong ===
          updated.maPhong
            ? {
                ...room,
                ...updated,
              }
            : room
        )
      );

      setMessage(
        data.message ||
          "Cập nhật phòng chiếu thành công."
      );
    } catch (err) {
      console.error(err);
      setError(
        err.message ||
          "Cập nhật phòng chiếu thất bại."
      );
    } finally {
      setSavingRoom(false);
    }
  };

  const getRoomStatusName = (
    status
  ) =>
    status === "HOAT_DONG"
      ? "Hoạt động"
      : "Ngừng hoạt động";

  const getSeatClass = (
    seatGroup
  ) => {
    const firstSeat =
      seatGroup[0];

    const allLocked =
      seatGroup.every(
        (seat) =>
          seat.trangThai === "KHOA"
      );

    const classes = [
      "pcm-seat",
    ];

    if (
      firstSeat.loaiGhe === "VIP"
    ) {
      classes.push("pcm-seat-vip");
    } else if (
      firstSeat.loaiGhe === "DOI"
    ) {
      classes.push(
        "pcm-seat-couple"
      );
    } else {
      classes.push(
        "pcm-seat-normal"
      );
    }

    if (seatGroup.length > 1) {
      classes.push(
        "pcm-seat-double"
      );
    }

    if (allLocked) {
      classes.push(
        "pcm-seat-locked"
      );
    }

    /*
     * Tạo lối đi giống trang bán vé:
     * ghế đơn: giữa cột 5 và 6.
     * ghế đôi: giữa cặp 3-4 và 5-6.
     */
    const aisleBefore =
      seatGroup.length > 1
        ? Number(firstSeat.cot) === 5
        : Number(firstSeat.cot) === 6;

    if (aisleBefore) {
      classes.push(
        "pcm-seat-aisle"
      );
    }

    return classes.join(" ");
  };

  return (
    <section className="pcm-page">
      <div className="pcm-heading">
        <div>
          <span className="pcm-eyebrow">
            CGV AEON MALL HÀ ĐÔNG
          </span>

          <h1>
            Quản lý phòng chiếu
          </h1>

          <p>
            Xem đúng sơ đồ ghế thực tế
            của từng phòng và khóa ghế
            hỏng/không sử dụng được.
          </p>
        </div>
      </div>

      {message && (
        <div className="pcm-alert pcm-alert-success">
          {message}
        </div>
      )}

      {error && (
        <div className="pcm-alert pcm-alert-error">
          {error}
        </div>
      )}

      {/* =========================
          TÌM KIẾM PHÒNG
      ========================= */}
      <form
        className="pcm-filter"
        onSubmit={handleSearch}
      >
        <div className="pcm-field">
          <label>
            Tìm phòng
          </label>

          <input
            type="text"
            value={keyword}
            placeholder="Nhập P001, Phòng 1..."
            onChange={(event) =>
              setKeyword(
                event.target.value
              )
            }
          />
        </div>

        <div className="pcm-field">
          <label>
            Trạng thái
          </label>

          <select
            value={statusFilter}
            onChange={(event) =>
              setStatusFilter(
                event.target.value
              )
            }
          >
            <option value="">
              Tất cả
            </option>

            <option value="HOAT_DONG">
              Hoạt động
            </option>

            <option value="NGUNG_HOAT_DONG">
              Ngừng hoạt động
            </option>
          </select>
        </div>

        <div className="pcm-filter-actions">
          <button
            type="submit"
            className="pcm-btn pcm-btn-primary"
          >
            Tìm kiếm
          </button>

          <button
            type="button"
            className="pcm-btn pcm-btn-light"
            onClick={
              handleReset
            }
          >
            Đặt lại
          </button>
        </div>
      </form>

      {/* =========================
          4 PHÒNG CHIẾU
      ========================= */}
      <div className="pcm-room-grid">
        {loadingRooms ? (
          <div className="pcm-empty">
            Đang tải danh sách phòng...
          </div>
        ) : visibleRooms.length ===
          0 ? (
          <div className="pcm-empty">
            Không có phòng phù hợp.
          </div>
        ) : (
          visibleRooms.map(
            (room) => (
              <button
                type="button"
                key={room.maPhong}
                className={`pcm-room-card ${
                  selectedRoom?.maPhong ===
                  room.maPhong
                    ? "active"
                    : ""
                }`}
                onClick={() =>
                  selectRoom(room)
                }
              >
                <div className="pcm-room-card-top">
                  <span>
                    {room.maPhong}
                  </span>

                  <span
                    className={`pcm-room-status ${
                      room.trangThai ===
                      "HOAT_DONG"
                        ? "active"
                        : "inactive"
                    }`}
                  >
                    {getRoomStatusName(
                      room.trangThai
                    )}
                  </span>
                </div>

                <h2>
                  {room.tenPhong}
                </h2>

                <p>
                  Sức chứa:{" "}
                  <strong>
                    {room.sucChua} ghế
                  </strong>
                </p>

                <small>
                  Bấm để xem sơ đồ ghế
                </small>
              </button>
            )
          )
        )}
      </div>

      {/* =========================
          CHI TIẾT SƠ ĐỒ PHÒNG
      ========================= */}
      {selectedRoom && (
        <div className="pcm-detail-card">
          <div className="pcm-detail-header">
            <div>
              <span className="pcm-room-code">
                {
                  selectedRoom.maPhong
                }
              </span>

              <h2>
                {
                  selectedRoom.tenPhong
                }
              </h2>

              <p>
                Sức chứa:{" "}
                <strong>
                  {
                    selectedRoom.sucChua
                  }{" "}
                  ghế
                </strong>
              </p>
            </div>

            <button
              type="button"
              className="pcm-btn pcm-btn-primary"
              onClick={
                openEditRoom
              }
            >
              Chỉnh sửa phòng
            </button>
          </div>

          {loadingSeatMap ? (
            <div className="pcm-empty">
              Đang tải sơ đồ ghế...
            </div>
          ) : !seatMap ? (
            <div className="pcm-empty">
              Phòng chưa có sơ đồ ghế.
            </div>
          ) : (
            <>
              {/* THỐNG KÊ */}
              <div className="pcm-stats">
                <div>
                  <span>
                    Tổng vị trí
                  </span>
                  <strong>
                    {seatStats.total}
                  </strong>
                </div>

                <div>
                  <span>
                    Ghế thường
                  </span>
                  <strong>
                    {seatStats.normal}
                  </strong>
                </div>

                <div>
                  <span>
                    Ghế VIP
                  </span>
                  <strong>
                    {seatStats.vip}
                  </strong>
                </div>

                <div>
                  <span>
                    Ghế đôi
                  </span>
                  <strong>
                    {seatStats.couplePairs}
                  </strong>
                </div>

                <div>
                  <span>
                    Ghế đang khóa
                  </span>
                  <strong>
                    {seatStats.locked}
                  </strong>
                </div>
              </div>

              {/* CHÚ THÍCH */}
              <div className="pcm-legend">
                <span>
                  <i className="pcm-legend-box pcm-seat-normal" />
                  Ghế thường
                </span>

                <span>
                  <i className="pcm-legend-box pcm-seat-vip" />
                  Ghế VIP
                </span>

                <span>
                  <i className="pcm-legend-box pcm-seat-couple" />
                  Ghế đôi
                </span>

                <span>
                  <i className="pcm-legend-box pcm-seat-locked" />
                  Ghế khóa
                </span>
              </div>

              {/* MÀN HÌNH */}
              <div className="pcm-screen">
                <span>
                  MÀN HÌNH CHIẾU
                </span>
              </div>

              {/* SƠ ĐỒ GHẾ */}
              <div className="pcm-seat-map">
                {seatGroups.map(
                  ([row, groups]) => (
                    <div
                      className="pcm-seat-row"
                      key={row}
                    >
                      <span className="pcm-row-label">
                        {row}
                      </span>

                      <div className="pcm-row-seats">
                        {groups.map(
                          (seatGroup) => {
                            const firstSeat =
                              seatGroup[0];

                            const label =
                              seatGroup
                                .map(
                                  (seat) =>
                                    `${seat.hang}${seat.cot}`
                                )
                                .join(
                                  " - "
                                );

                            const allLocked =
                              seatGroup.every(
                                (seat) =>
                                  seat.trangThai ===
                                  "KHOA"
                              );

                            return (
                              <button
                                type="button"
                                key={seatGroup
                                  .map(
                                    (seat) =>
                                      seat.maGhe
                                  )
                                  .join(
                                    "-"
                                  )}
                                className={getSeatClass(
                                  seatGroup
                                )}
                                onClick={() =>
                                  handleToggleSeatGroup(
                                    seatGroup
                                  )
                                }
                                title={
                                  allLocked
                                    ? `${label} - Đang khóa`
                                    : `${label} - Hoạt động`
                                }
                              >
                                {seatGroup.length >
                                1
                                  ? label
                                  : `${firstSeat.hang}${firstSeat.cot}`}
                              </button>
                            );
                          }
                        )}
                      </div>

                      <span className="pcm-row-label">
                        {row}
                      </span>
                    </div>
                  )
                )}
              </div>

              <div className="pcm-seat-note">
                Bấm vào ghế để khóa vị
                trí hỏng/không sử dụng
                được. Với ghế đôi, hệ
                thống khóa hoặc mở lại
                cả cặp.
              </div>
            </>
          )}
        </div>
      )}

      {/* =========================
          MODAL SỬA PHÒNG
      ========================= */}
      {editing &&
        selectedRoom && (
          <div
            className="pcm-modal-backdrop"
            onMouseDown={() =>
              !savingRoom &&
              setEditing(false)
            }
          >
            <div
              className="pcm-modal"
              onMouseDown={(
                event
              ) =>
                event.stopPropagation()
              }
            >
              <div className="pcm-modal-header">
                <div>
                  <h2>
                    Chỉnh sửa phòng
                  </h2>
                  <p>
                    {
                      selectedRoom.maPhong
                    }
                  </p>
                </div>

                <button
                  type="button"
                  className="pcm-modal-close"
                  onClick={() =>
                    setEditing(false)
                  }
                  disabled={
                    savingRoom
                  }
                >
                  ×
                </button>
              </div>

              <form
                onSubmit={saveRoom}
              >
                <div className="pcm-field">
                  <label>
                    Mã phòng
                  </label>

                  <input
                    type="text"
                    value={
                      selectedRoom.maPhong
                    }
                    disabled
                  />
                </div>

                <div className="pcm-field">
                  <label>
                    Tên phòng
                  </label>

                  <input
                    type="text"
                    value={
                      roomForm.tenPhong
                    }
                    onChange={(
                      event
                    ) =>
                      setRoomForm(
                        (current) => ({
                          ...current,
                          tenPhong:
                            event
                              .target
                              .value,
                        })
                      )
                    }
                    required
                  />
                </div>

                <div className="pcm-field">
                  <label>
                    Sức chứa
                  </label>

                  <input
                    type="text"
                    value={`${selectedRoom.sucChua} ghế`}
                    disabled
                  />
                </div>

                <div className="pcm-field">
                  <label>
                    Trạng thái
                  </label>

                  <select
                    value={
                      roomForm.trangThai
                    }
                    onChange={(
                      event
                    ) =>
                      setRoomForm(
                        (current) => ({
                          ...current,
                          trangThai:
                            event
                              .target
                              .value,
                        })
                      )
                    }
                  >
                    <option value="HOAT_DONG">
                      Hoạt động
                    </option>

                    <option value="NGUNG_HOAT_DONG">
                      Ngừng hoạt động
                    </option>
                  </select>
                </div>

                <div className="pcm-modal-actions">
                  <button
                    type="button"
                    className="pcm-btn pcm-btn-light"
                    onClick={() =>
                      setEditing(false)
                    }
                    disabled={
                      savingRoom
                    }
                  >
                    Hủy
                  </button>

                  <button
                    type="submit"
                    className="pcm-btn pcm-btn-primary"
                    disabled={
                      savingRoom
                    }
                  >
                    {savingRoom
                      ? "Đang lưu..."
                      : "Lưu thay đổi"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}
    </section>
  );
}

export default PhongChieuManagement;
