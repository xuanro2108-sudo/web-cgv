
import { useEffect, useRef, useState } from "react";

import "./CustomerManagement.css";
import "./PromotionManagement.css";

const api = (
    import.meta.env.VITE_API_URL ||
    "http://127.0.0.1:8000/api"
).replace(/\/$/, "");

const money = (value) =>
    new Intl.NumberFormat("vi-VN", {
        style: "currency",
        currency: "VND",
    }).format(Number(value) || 0);

const day = (value) => value?.slice(0, 10) || "";

const date = (value) =>
    day(value)
        .split("-")
        .reverse()
        .join("/");

const empty = {
    maKM: "",
    tenKM: "",
    hinhAnh: "",
    hinhThuc: "GIAM_PHAN_TRAM",
    giaTri: "",
    donToiThieu: "0",
    ngayBatDau: "",
    ngayKetThuc: "",
    trangThai: "HOAT_DONG",
};

async function request(path, options = {}) {
    const response = await fetch(api + path, {
        ...options,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            Authorization: `Bearer ${
                localStorage.getItem("token") || ""
            }`,
            ...(options.headers || {}),
        },
    });

    const body = await response.json().catch(() => null);

    if (!response.ok) {
        const error = new Error(
            response.status === 401
                ? "Phiên đăng nhập đã hết hạn. Vui lòng đăng nhập lại."
                : response.status === 403
                ? "Bạn không có quyền quản lý khuyến mại."
                : body?.message || "Không thể thực hiện yêu cầu."
        );

        error.fields = body?.errors;
        throw error;
    }

    if (!body) {
        throw new Error("Dữ liệu phản hồi không hợp lệ.");
    }

    return body;
}

function PromotionImage({
    src,
    alt = "Ảnh khuyến mại",
    thumbnail = false,
}) {
    const [failed, setFailed] = useState(false);

    useEffect(() => {
        setFailed(false);
    }, [src]);

    if (!src || failed) {
        return (
            <div
                className={
                    thumbnail
                        ? "pm-thumbnail pm-image-placeholder"
                        : "pm-preview pm-image-placeholder"
                }
            >
                {src ? "Không tải được ảnh" : "Chưa có ảnh"}
            </div>
        );
    }

    return (
        <img
            className={thumbnail ? "pm-thumbnail" : "pm-preview"}
            src={src}
            alt={alt}
            onError={() => setFailed(true)}
        />
    );
}

function PromotionDialog({
    mode,
    item,
    onClose,
    onSaved,
}) {
    const dialog = useRef(null);

    const [form, setForm] = useState(() => {
        if (!item) {
            return { ...empty };
        }

        return Object.fromEntries(
            Object.keys(empty).map((key) => [
                key,
                key.startsWith("ngay")
                    ? day(item[key])
                    : String(item[key] ?? ""),
            ])
        );
    });

    const [busy, setBusy] = useState(false);
    const [error, setError] = useState("");
    const [fields, setFields] = useState({});

    const viewing = mode === "view";

    const used =
        Number(item?.don_hangs_count) > 0;

    useEffect(() => {
        const previous = document.activeElement;

        dialog.current?.showModal();

        const overflow = document.body.style.overflow;
        document.body.style.overflow = "hidden";

        return () => {
            document.body.style.overflow = overflow;
            previous?.focus?.();
        };
    }, []);

    const close = () => {
        if (!busy) {
            onClose();
        }
    };

    const change = (event) => {
        const { name, value } = event.target;

        setForm((previous) => ({
            ...previous,
            [name]: value,
        }));

        setFields((previous) => ({
            ...previous,
            [name]: undefined,
        }));

        setError("");
    };

    const validateUrl = (value) => {
        if (!value) {
            return true;
        }

        try {
            new URL(value);
            return true;
        } catch {
            return false;
        }
    };

    async function submit(event) {
        event.preventDefault();

        if (busy) {
            return;
        }

        setBusy(true);
        setError("");
        setFields({});

        try {
            let payload = {};

            if (mode === "create") {
                payload = { ...form };
            }

            if (mode === "edit") {
                if (used) {
                    payload = {
                        tenKM: form.tenKM,
                        hinhAnh: form.hinhAnh || null,
                        trangThai: form.trangThai,
                    };
                } else {
                    payload = Object.fromEntries(
                        Object.entries(form).filter(
                            ([key, value]) => {
                                if (key === "maKM") {
                                    return false;
                                }

                                const oldValue =
                                    key.startsWith("ngay")
                                        ? day(item[key])
                                        : String(item[key] ?? "");

                                return (
                                    String(value ?? "") !==
                                    String(oldValue ?? "")
                                );
                            }
                        )
                    );
                }
            }

            if (
                payload.hinhAnh !== undefined &&
                payload.hinhAnh !== null &&
                payload.hinhAnh !== ""
            ) {
                if (!validateUrl(payload.hinhAnh)) {
                    setFields({
                        hinhAnh: ["Link ảnh không hợp lệ."],
                    });

                    setError(
                        "Vui lòng nhập URL ảnh hợp lệ."
                    );

                    return;
                }
            }

            if (
                mode === "edit" &&
                !used &&
                Object.keys(payload).length === 0
            ) {
                setError("Chưa có thay đổi để lưu.");
                return;
            }

            if (
                payload.ngayBatDau &&
                payload.ngayKetThuc &&
                payload.ngayKetThuc < payload.ngayBatDau
            ) {
                setFields({
                    ngayKetThuc: [
                        "Ngày kết thúc phải từ ngày bắt đầu trở đi.",
                    ],
                });

                setError(
                    "Khoảng thời gian khuyến mại không hợp lệ."
                );

                return;
            }

            if (
                payload.hinhThuc === "GIAM_PHAN_TRAM" &&
                Number(payload.giaTri) > 100
            ) {
                setFields({
                    giaTri: [
                        "Giá trị giảm phần trăm không được vượt quá 100%.",
                    ],
                });

                setError("Giá trị giảm không hợp lệ.");

                return;
            }

            if (
                payload.giaTri !== undefined &&
                payload.giaTri !== "" &&
                payload.giaTri !== null
            ) {
                payload.giaTri = Number(payload.giaTri);
            }

            if (
                payload.donToiThieu !== undefined &&
                payload.donToiThieu !== "" &&
                payload.donToiThieu !== null
            ) {
                payload.donToiThieu = Number(
                    payload.donToiThieu
                );
            }

            Object.keys(payload).forEach((key) => {
                if (
                    typeof payload[key] === "string" &&
                    payload[key].trim() === ""
                ) {
                    payload[key] = null;
                }
            });

            const path =
                "/khuyen-mais" +
                (item
                    ? "/" + encodeURIComponent(item.maKM)
                    : "");

            const method =
                mode === "delete"
                    ? "DELETE"
                    : mode === "edit"
                    ? "PATCH"
                    : "POST";

            await request(path, {
                method,
                ...(mode !== "delete" && {
                    body: JSON.stringify(payload),
                }),
            });

            onSaved(
                mode === "delete"
                    ? "Đã ngừng áp dụng khuyến mại. Lịch sử đơn hàng được giữ lại."
                    : mode === "edit"
                    ? "Đã cập nhật khuyến mại."
                    : "Đã thêm khuyến mại."
            );
        } catch (error) {
            setError(error.message);
            setFields(error.fields || {});
        } finally {
            setBusy(false);
        }
    }

    const field = (
        name,
        title,
        type = "text",
        extra = {}
    ) => (
        <label key={name}>
            {title}

            <input
                name={name}
                type={type}
                value={form[name] ?? ""}
                onChange={change}
                required={
                    mode === "create" &&
                    [
                        "maKM",
                        "tenKM",
                        "hinhThuc",
                        "giaTri",
                        "ngayBatDau",
                        "ngayKetThuc",
                    ].includes(name)
                }
                disabled={
                    viewing ||
                    busy ||
                    (name === "maKM" && mode === "edit") ||
                    (used &&
                        ![
                            "tenKM",
                            "hinhAnh",
                            "trangThai",
                        ].includes(name))
                }
                aria-invalid={!!fields[name]}
                {...extra}
            />

            {fields[name] && (
                <small className="pm-field-error">
                    {fields[name].join(" ")}
                </small>
            )}
        </label>
    );

    return (
        <dialog
            ref={dialog}
            className="cm-dialog pm-dialog"
            aria-labelledby="pm-title"
            onCancel={(event) => {
                event.preventDefault();
                close();
            }}
        >
            <div className="cm-dialog-content">
                <header className="cm-heading">
                    <div>
                        <h2 id="pm-title">
                            {
                                {
                                    create: "Thêm khuyến mại",
                                    edit: "Sửa khuyến mại",
                                    view: "Thông tin khuyến mại",
                                    delete: "Xóa khuyến mại?",
                                }[mode]
                            }
                        </h2>
                    </div>

                    <button
                        type="button"
                        disabled={busy}
                        onClick={close}
                        aria-label="Đóng"
                    >
                        ×
                    </button>
                </header>

                <form onSubmit={submit}>
                    {mode === "delete" ? (
                        <>
                            <p>
                                Bạn muốn xóa khuyến mại{" "}
                                <strong>
                                    {item.maKM} — {item.tenKM}
                                </strong>
                                ?
                            </p>

                            <p className="cm-demo">
                                Mã sẽ chuyển sang Ngừng hoạt động
                                và không thể áp dụng cho đơn hàng
                                mới. Thông tin khuyến mại và lịch
                                sử đơn hàng vẫn được giữ lại.
                            </p>
                        </>
                    ) : (
                        <>
                            {used && (
                                <p className="cm-demo">
                                    Mã đã liên kết với{" "}
                                    {item.don_hangs_count} đơn hàng.
                                    Có thể sửa tên, link ảnh và
                                    trạng thái; điều kiện giảm và
                                    thời gian áp dụng không thể
                                    thay đổi.
                                </p>
                            )}

                            <div className="pm-form">
                                <div className="pm-photo">
                                    <label>
                                        Link ảnh khuyến mại

                                        <input
                                            name="hinhAnh"
                                            type="url"
                                            value={
                                                form.hinhAnh ?? ""
                                            }
                                            onChange={change}
                                            disabled={
                                                viewing || busy
                                            }
                                            maxLength={500}
                                            placeholder="https://example.com/khuyen-mai.jpg"
                                            aria-invalid={
                                                !!fields.hinhAnh
                                            }
                                        />
                                    </label>

                                    {fields.hinhAnh && (
                                        <small className="pm-field-error">
                                            {fields.hinhAnh.join(" ")}
                                        </small>
                                    )}

                                    {form.hinhAnh ? (
                                        <PromotionImage
                                            src={form.hinhAnh}
                                            alt={
                                                form.tenKM ||
                                                "Ảnh khuyến mại"
                                            }
                                        />
                                    ) : (
                                        <p>Chưa có ảnh</p>
                                    )}

                                    {!viewing && (
                                        <small>
                                            Nhập đường dẫn trực tiếp
                                            đến ảnh JPG, PNG hoặc
                                            WebP.
                                        </small>
                                    )}
                                </div>

                                {field(
                                    "maKM",
                                    "Mã khuyến mại",
                                    "text",
                                    {
                                        maxLength: 50,
                                        pattern: "[A-Z0-9_-]+",
                                        title:
                                            "Chỉ dùng chữ in hoa, số, dấu gạch ngang hoặc dấu gạch dưới",
                                    }
                                )}

                                {field(
                                    "tenKM",
                                    "Tên khuyến mại",
                                    "text",
                                    {
                                        maxLength: 255,
                                    }
                                )}

                                <label>
                                    Hình thức

                                    <select
                                        name="hinhThuc"
                                        value={form.hinhThuc}
                                        onChange={change}
                                        disabled={
                                            viewing ||
                                            busy ||
                                            used
                                        }
                                    >
                                        <option value="GIAM_PHAN_TRAM">
                                            Giảm phần trăm
                                        </option>

                                        <option value="GIAM_GIA">
                                            Giảm số tiền
                                        </option>
                                    </select>
                                </label>

                                {field(
                                    "giaTri",
                                    form.hinhThuc ===
                                        "GIAM_PHAN_TRAM"
                                        ? "Giá trị giảm (%)"
                                        : "Giá trị giảm (đ)",
                                    "number",
                                    {
                                        min: 0.01,
                                        max:
                                            form.hinhThuc ===
                                            "GIAM_PHAN_TRAM"
                                                ? 100
                                                : 99999999.99,
                                        step: "0.01",
                                    }
                                )}

                                {field(
                                    "donToiThieu",
                                    "Đơn tối thiểu (đ)",
                                    "number",
                                    {
                                        min: 0,
                                        max: 99999999.99,
                                        step: "0.01",
                                    }
                                )}

                                <label>
                                    Trạng thái

                                    <select
                                        name="trangThai"
                                        value={form.trangThai}
                                        onChange={change}
                                        disabled={
                                            viewing || busy
                                        }
                                    >
                                        <option value="HOAT_DONG">
                                            Hoạt động
                                        </option>

                                        <option value="NGUNG_HOAT_DONG">
                                            Ngừng hoạt động
                                        </option>
                                    </select>
                                </label>

                                {field(
                                    "ngayBatDau",
                                    "Ngày bắt đầu",
                                    "date"
                                )}

                                {field(
                                    "ngayKetThuc",
                                    "Ngày kết thúc",
                                    "date",
                                    {
                                        min: form.ngayBatDau,
                                    }
                                )}
                            </div>
                        </>
                    )}

                    {error && (
                        <p
                            className="cm-error"
                            role="alert"
                        >
                            {error}
                        </p>
                    )}

                    <footer className="cm-modal-actions">
                        <button
                            type="button"
                            disabled={busy}
                            onClick={close}
                        >
                            {viewing ? "Đóng" : "Hủy"}
                        </button>

                        {!viewing && (
                            <button
                                type="submit"
                                className="cm-delete-button"
                                disabled={busy}
                            >
                                {busy
                                    ? "Đang xử lý…"
                                    : mode === "delete"
                                    ? "Xác nhận xóa"
                                    : "Lưu khuyến mại"}
                            </button>
                        )}
                    </footer>
                </form>
            </div>
        </dialog>
    );
}

export default function PromotionManagement() {
    const [q, setQ] = useState("");
    const [status, setStatus] = useState("");
    const [page, setPage] = useState(1);
    const [revision, setRevision] = useState(0);
    const [result, setResult] = useState(null);
    const [modal, setModal] = useState(null);
    const [notice, setNotice] = useState("");

    const params = new URLSearchParams({
        q: q.trim(),
        trangThai: status,
        page,
    }).toString();

    const key = `${params}:${revision}`;

    useEffect(() => {
        let active = true;

        const controller = new AbortController();

        const timer = setTimeout(() => {
            request(
                "/quan-ly/khuyen-mais?" + params,
                {
                    signal: controller.signal,
                }
            )
                .then((data) => {
                    if (active) {
                        setResult({
                            key,
                            data,
                        });
                    }
                })
                .catch((error) => {
                    if (
                        active &&
                        error.name !== "AbortError"
                    ) {
                        setResult({
                            key,
                            error: error.message,
                        });
                    }
                });
        }, 250);

        return () => {
            active = false;
            clearTimeout(timer);
            controller.abort();
        };
    }, [key, params]);

    const loading = result?.key !== key;

    const data = loading
        ? null
        : result?.data;

    const error = loading
        ? null
        : result?.error;

    const now = new Date();

    const today = `${now.getFullYear()}-${String(
        now.getMonth() + 1
    ).padStart(2, "0")}-${String(
        now.getDate()
    ).padStart(2, "0")}`;

    const period = (item) => {
        if (item.trangThai !== "HOAT_DONG") {
            return "Ngừng hoạt động";
        }

        if (day(item.ngayBatDau) > today) {
            return "Chưa bắt đầu";
        }

        if (day(item.ngayKetThuc) < today) {
            return "Hết hạn";
        }

        return "Đang áp dụng";
    };

    return (
        <section className="cm-page">
            <p className="cm-breadcrumb">
                Hệ thống quản lý / Khuyến mại
            </p>

            <header className="cm-heading">
                <div>
                    <h1>Quản lý khuyến mại</h1>

                    <p>
                        Theo dõi ưu đãi và quản lý các chương
                        trình giảm giá.
                    </p>
                </div>

                <button
                    type="button"
                    onClick={() =>
                        setModal({
                            mode: "create",
                        })
                    }
                >
                    + Thêm khuyến mại
                </button>
            </header>

            {notice && (
                <div
                    className="cm-notice"
                    role="status"
                >
                    {notice}

                    <button
                        onClick={() => setNotice("")}
                        aria-label="Đóng thông báo"
                    >
                        ×
                    </button>
                </div>
            )}

            <section className="cm-card">
                <div className="cm-card-heading">
                    <h2>
                        Danh sách khuyến mại{" "}
                        {data && <span>{data.total}</span>}
                    </h2>
                </div>

                <div className="cm-filters">
                    <label className="cm-search">
                        Tra cứu khuyến mại

                        <input
                            type="search"
                            maxLength={255}
                            placeholder="Nhập mã hoặc tên khuyến mại…"
                            value={q}
                            onChange={(event) => {
                                setQ(event.target.value);
                                setPage(1);
                            }}
                        />
                    </label>

                    <label>
                        Trạng thái

                        <select
                            value={status}
                            onChange={(event) => {
                                setStatus(event.target.value);
                                setPage(1);
                            }}
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
                    </label>

                    <button
                        onClick={() => {
                            setQ("");
                            setStatus("");
                            setPage(1);
                        }}
                    >
                        Xóa bộ lọc
                    </button>
                </div>

                {loading && (
                    <p
                        className="cm-empty"
                        role="status"
                    >
                        Đang tải khuyến mại…
                    </p>
                )}

                {error && (
                    <div
                        className="cm-error"
                        role="alert"
                    >
                        {error}

                        <button
                            onClick={() =>
                                setRevision(
                                    (value) => value + 1
                                )
                            }
                        >
                            Thử lại
                        </button>
                    </div>
                )}

                {data && (
                    <>
                        <div className="cm-table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Khuyến mại</th>
                                        <th>Mức giảm</th>
                                        <th>Đơn tối thiểu</th>
                                        <th>Thời gian</th>
                                        <th>Tình trạng</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    {data.data.map((item) => {
                                        const statusText =
                                            period(item);

                                        return (
                                            <tr
                                                key={item.maKM}
                                            >
                                                <td>
                                                    <div className="pm-promotion-info">
                                                        <PromotionImage
                                                            src={
                                                                item.hinhAnh
                                                            }
                                                            alt={
                                                                item.tenKM
                                                            }
                                                            thumbnail
                                                        />

                                                        <div className="pm-promotion-content">
                                                            <strong className="pm-promotion-name">
                                                                {
                                                                    item.tenKM
                                                                }
                                                            </strong>

                                                            <small className="pm-promotion-code">
                                                                Mã:{" "}
                                                                {
                                                                    item.maKM
                                                                }
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>

                                                <td>
                                                    {item.hinhThuc ===
                                                    "GIAM_PHAN_TRAM"
                                                        ? `${Number(
                                                              item.giaTri
                                                          )}%`
                                                        : money(
                                                              item.giaTri
                                                          )}
                                                </td>

                                                <td>
                                                    {money(
                                                        item.donToiThieu
                                                    )}
                                                </td>

                                                <td>
                                                    {date(
                                                        item.ngayBatDau
                                                    )}

                                                    <small>
                                                        đến{" "}
                                                        {date(
                                                            item.ngayKetThuc
                                                        )}
                                                    </small>
                                                </td>

                                                <td>
                                                    <span
                                                        className={`cm-badge ${
                                                            statusText ===
                                                            "Đang áp dụng"
                                                                ? "cm-green"
                                                                : "cm-gray"
                                                        }`}
                                                    >
                                                        {
                                                            statusText
                                                        }
                                                    </span>
                                                </td>

                                                <td>
                                                    <div className="cm-actions">
                                                        <button
                                                            onClick={() =>
                                                                setModal(
                                                                    {
                                                                        mode: "view",
                                                                        item,
                                                                    }
                                                                )
                                                            }
                                                        >
                                                            Chi tiết
                                                        </button>

                                                        <button
                                                            onClick={() =>
                                                                setModal(
                                                                    {
                                                                        mode: "edit",
                                                                        item,
                                                                    }
                                                                )
                                                            }
                                                        >
                                                            Sửa
                                                        </button>

                                                        <button
                                                            className="cm-danger"
                                                            disabled={
                                                                item.trangThai ===
                                                                "NGUNG_HOAT_DONG"
                                                            }
                                                            onClick={() =>
                                                                setModal(
                                                                    {
                                                                        mode: "delete",
                                                                        item,
                                                                    }
                                                                )
                                                            }
                                                        >
                                                            Xóa
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>

                        {!data.data.length && (
                            <p className="cm-empty">
                                Không tìm thấy khuyến mại phù hợp.
                            </p>
                        )}

                        <footer className="cm-pagination">
                            <span>
                                {data.total} khuyến mại
                            </span>

                            <div>
                                <button
                                    disabled={page <= 1}
                                    onClick={() =>
                                        setPage(page - 1)
                                    }
                                >
                                    Trước
                                </button>

                                <span>
                                    Trang {page} /{" "}
                                    {data.last_page}
                                </span>

                                <button
                                    disabled={
                                        page >=
                                        data.last_page
                                    }
                                    onClick={() =>
                                        setPage(page + 1)
                                    }
                                >
                                    Sau
                                </button>
                            </div>
                        </footer>
                    </>
                )}
            </section>

            {modal && (
                <PromotionDialog
                    {...modal}
                    onClose={() => setModal(null)}
                    onSaved={(message) => {
                        setModal(null);
                        setNotice(message);
                        setPage(1);
                        setRevision(
                            (value) => value + 1
                        );
                    }}
                />
            )}
        </section>
    );
}
