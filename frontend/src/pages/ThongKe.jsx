import {
  useEffect,
  useRef,
  useState,
} from "react";

import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  BarElement,
  Tooltip,
  Legend,
} from "chart.js";

import {
  Bar,
} from "react-chartjs-2";

import * as XLSX from "xlsx";

import html2canvas from "html2canvas";

import jsPDF from "jspdf";

import "./ThongKe.css";


ChartJS.register(
  CategoryScale,
  LinearScale,
  BarElement,
  Tooltip,
  Legend
);


const API =
  "http://127.0.0.1:8000/api/quan-ly/thong-ke";


function ThongKe() {

  const reportRef =
    useRef(null);

  const [
    loaiThongKe,
    setLoaiThongKe,
  ] =
    useState("ve");

  const [
    tuNgay,
    setTuNgay,
  ] =
    useState("");

  const [
    denNgay,
    setDenNgay,
  ] =
    useState("");

  const [
    maPhim,
    setMaPhim,
  ] =
    useState("");

  const [
    maKM,
    setMaKM,
  ] =
    useState("");

  const [
    dsPhim,
    setDsPhim,
  ] =
    useState([]);

  const [
    dsKhuyenMai,
    setDsKhuyenMai,
  ] =
    useState([]);

  const [
    data,
    setData,
  ] =
    useState(null);

  const [
    loading,
    setLoading,
  ] =
    useState(false);

  const [
    error,
    setError,
  ] =
    useState("");


  const token =
    localStorage.getItem(
      "token"
    );


  useEffect(() => {

    const headers = {
      Accept:
        "application/json",

      Authorization:
        `Bearer ${token}`,
    };


    Promise.all([
      fetch(
        `${API}/danh-sach-phim`,
        { headers }
      ),

      fetch(
        `${API}/danh-sach-khuyen-mai`,
        { headers }
      ),
    ])
      .then(async ([
        phimRes,
        kmRes,
      ]) => {

        if (
          phimRes.ok
        ) {
          const phim =
            await phimRes.json();

          setDsPhim(
            phim.data || []
          );
        }


        if (
          kmRes.ok
        ) {
          const km =
            await kmRes.json();

          setDsKhuyenMai(
            km.data || []
          );
        }
      })
      .catch(() => {});

  }, [token]);


  const formatMoney =
    (value) =>
      Number(
        value || 0
      ).toLocaleString(
        "vi-VN"
      ) + " đ";


  const formatDate =
    (date) => {

      if (!date) {
        return "";
      }

      return new Date(
        date + "T00:00:00"
      ).toLocaleDateString(
        "vi-VN"
      );
    };


  const changeTab =
    (tab) => {

      setLoaiThongKe(
        tab
      );

      setData(null);

      setError("");
    };


  const getEndpoint =
    () => {

      if (
        loaiThongKe ===
        "combo"
      ) {
        return "doanh-thu-combo";
      }

      if (
        loaiThongKe ===
        "phim"
      ) {
        return "theo-phim";
      }

      if (
        loaiThongKe ===
        "khuyen-mai"
      ) {
        return "khuyen-mai";
      }

      return "doanh-thu-ve";
    };


  const handleThongKe =
    async () => {

      setError("");
      setData(null);


      if (
        !tuNgay ||
        !denNgay
      ) {
        setError(
          "Vui lòng chọn đầy đủ khoảng thời gian."
        );

        return;
      }


      if (
        tuNgay > denNgay
      ) {
        setError(
          "Đến ngày phải lớn hơn hoặc bằng từ ngày."
        );

        return;
      }


      const params =
        new URLSearchParams({
          tuNgay,
          denNgay,
        });


      if (
        loaiThongKe ===
          "phim"
        &&
        maPhim
      ) {
        params.append(
          "maPhim",
          maPhim
        );
      }


      if (
        loaiThongKe ===
          "khuyen-mai"
        &&
        maKM
      ) {
        params.append(
          "maKM",
          maKM
        );
      }


      setLoading(true);


      try {

        const response =
          await fetch(
            `${API}/${getEndpoint()}?${params.toString()}`,
            {
              headers: {
                Accept:
                  "application/json",

                Authorization:
                  `Bearer ${token}`,
              },
            }
          );


        const result =
          await response.json();


        if (!response.ok) {
          throw new Error(
            result.message ||
              "Không thể tải dữ liệu thống kê."
          );
        }


        setData(
          result
        );

      } catch (err) {

        setError(
          err.message
        );

      } finally {

        setLoading(false);
      }
    };


  const getChartData =
    () => {

      if (
        loaiThongKe ===
        "combo"
      ) {

        return {
          labels:
            data?.chiTiet?.map(
              (item) =>
                item.tenCombo
            ) || [],

          datasets: [
            {
              label:
                "Doanh thu combo",

              data:
                data?.chiTiet?.map(
                  (item) =>
                    Number(
                      item.doanhThu
                    )
                ) || [],

              backgroundColor:
                "#8f1025",

              borderRadius:
                6,
            },
          ],
        };
      }


      if (
        loaiThongKe ===
        "phim"
      ) {

        return {
          labels:
            data?.chiTiet?.map(
              (item) =>
                item.tenPhim
            ) || [],

          datasets: [
            {
              label:
                "Doanh thu theo phim",

              data:
                data?.chiTiet?.map(
                  (item) =>
                    Number(
                      item.doanhThu
                    )
                ) || [],

              backgroundColor:
                "#e31837",

              borderRadius:
                6,
            },
          ],
        };
      }


      if (
        loaiThongKe ===
        "khuyen-mai"
      ) {

        return {
          labels:
            data?.chiTiet?.map(
              (item) =>
                item.tenKM
            ) || [],

          datasets: [
            {
              label:
                "Doanh thu mang lại",

              data:
                data?.chiTiet?.map(
                  (item) =>
                    Number(
                      item.doanhThu
                    )
                ) || [],

              backgroundColor:
                "#a3152e",

              borderRadius:
                6,
            },
          ],
        };
      }


      return {
        labels:
          data?.theoNgay?.map(
            (item) =>
              formatDate(
                item.ngay
              )
          ) || [],

        datasets: [
          {
            label:
              "Doanh thu bán vé",

            data:
              data?.theoNgay?.map(
                (item) =>
                  Number(
                    item.doanhThu
                  )
              ) || [],

            backgroundColor:
              "#e31837",

            borderRadius:
              6,
          },
        ],
      };
    };


  const chartOptions = {

    responsive: true,

    maintainAspectRatio:
      false,

    plugins: {

      legend: {
        position:
          "top",
      },

      tooltip: {

        callbacks: {

          label:
            (context) =>
              formatMoney(
                context.raw
              ),
        },
      },
    },

    scales: {

      y: {

        beginAtZero:
          true,

        ticks: {

          callback:
            (value) =>
              Number(
                value
              ).toLocaleString(
                "vi-VN"
              ),
        },
      },
    },
  };


  const getExcelData =
    () => {

      if (
        loaiThongKe ===
        "combo"
      ) {

        return [
          [
            "Mã combo",
            "Tên combo",
            "Số lượng",
            "Doanh thu",
          ],

          ...data.chiTiet.map(
            (item) => [
              item.maCombo,
              item.tenCombo,
              Number(
                item.soLuong
              ),
              Number(
                item.doanhThu
              ),
            ]
          ),
        ];
      }


      if (
        loaiThongKe ===
        "phim"
      ) {

        return [
          [
            "Mã phim",
            "Tên phim",
            "Số vé",
            "Doanh thu",
            "Tổng chỗ",
            "Tỷ lệ lấp ghế (%)",
          ],

          ...data.chiTiet.map(
            (item) => [
              item.maPhim,
              item.tenPhim,
              Number(
                item.soVe
              ),
              Number(
                item.doanhThu
              ),
              Number(
                item.tongCho
              ),
              Number(
                item.tyLeLapGhe
              ),
            ]
          ),
        ];
      }


      if (
        loaiThongKe ===
        "khuyen-mai"
      ) {

        return [
          [
            "Mã KM",
            "Tên khuyến mại",
            "Lượt sử dụng",
            "Tiền giảm",
            "Doanh thu",
          ],

          ...data.chiTiet.map(
            (item) => [
              item.maKM,
              item.tenKM,
              Number(
                item.soLuotSuDung
              ),
              Number(
                item.tongTienGiam
              ),
              Number(
                item.doanhThu
              ),
            ]
          ),
        ];
      }


      return [
        [
          "Ngày",
          "Số vé",
          "Doanh thu",
        ],

        ...data.theoNgay.map(
          (item) => [
            formatDate(
              item.ngay
            ),
            Number(
              item.soVe
            ),
            Number(
              item.doanhThu
            ),
          ]
        ),
      ];
    };


  const exportExcel =
    () => {

      if (
        !data?.coDuLieu
      ) {
        return;
      }


      const rows = [
        [
          "BÁO CÁO THỐNG KÊ CGV"
        ],

        [
          "Từ ngày",
          formatDate(
            tuNgay
          )
        ],

        [
          "Đến ngày",
          formatDate(
            denNgay
          )
        ],

        [],

        ...getExcelData(),
      ];


      const worksheet =
        XLSX.utils
          .aoa_to_sheet(
            rows
          );


      worksheet[
        "!cols"
      ] = [
        { wch: 18 },
        { wch: 35 },
        { wch: 18 },
        { wch: 20 },
        { wch: 20 },
        { wch: 20 },
      ];


      const workbook =
        XLSX.utils
          .book_new();


      XLSX.utils
        .book_append_sheet(
          workbook,
          worksheet,
          "Thong ke"
        );


      XLSX.writeFile(
        workbook,
        `thong-ke-${loaiThongKe}-${tuNgay}-${denNgay}.xlsx`
      );
    };


  const exportPDF =
    async () => {

      if (
        !data?.coDuLieu
        ||
        !reportRef.current
      ) {
        return;
      }


      const canvas =
        await html2canvas(
          reportRef.current,
          {
            scale: 2,

            backgroundColor:
              "#ffffff",
          }
        );


      const image =
        canvas.toDataURL(
          "image/png"
        );


      const pdf =
        new jsPDF(
          "p",
          "mm",
          "a4"
        );


      const width =
        190;

      const height =
        canvas.height
        * width
        / canvas.width;


      const pageHeight =
        277;


      let heightLeft =
        height;

      let position =
        10;


      pdf.addImage(
        image,
        "PNG",
        10,
        position,
        width,
        height
      );


      heightLeft -=
        pageHeight;


      while (
        heightLeft > 0
      ) {

        position =
          10
          -
          (
            height
            -
            heightLeft
          );


        pdf.addPage();


        pdf.addImage(
          image,
          "PNG",
          10,
          position,
          width,
          height
        );


        heightLeft -=
          pageHeight;
      }


      pdf.save(
        `thong-ke-${loaiThongKe}-${tuNgay}-${denNgay}.pdf`
      );
    };


  const renderSummary =
    () => {

      if (
        loaiThongKe ===
        "combo"
      ) {

        return (
          <>
            <Summary
              title="Tổng doanh thu combo"
              value={
                formatMoney(
                  data.tongDoanhThu
                )
              }
            />

            <Summary
              title="Tổng số lượng bán"
              value={
                data.tongSoLuong
              }
            />

            <Summary
              title="Khoảng thời gian"
              value={
                `${formatDate(
                  tuNgay
                )} - ${formatDate(
                  denNgay
                )}`
              }
            />
          </>
        );
      }


      if (
        loaiThongKe ===
        "phim"
      ) {

        return (
          <>
            <Summary
              title="Tổng doanh thu"
              value={
                formatMoney(
                  data.tongDoanhThu
                )
              }
            />

            <Summary
              title="Tổng số vé"
              value={
                `${data.tongSoVe} vé`
              }
            />

            <Summary
              title="Tỷ lệ lấp ghế"
              value={
                `${data.tyLeLapGhe}%`
              }
            />
          </>
        );
      }


      if (
        loaiThongKe ===
        "khuyen-mai"
      ) {

        return (
          <>
            <Summary
              title="Lượt sử dụng"
              value={
                data.tongLuotSuDung
              }
            />

            <Summary
              title="Tổng tiền giảm"
              value={
                formatMoney(
                  data.tongTienGiam
                )
              }
            />

            <Summary
              title="Doanh thu mang lại"
              value={
                formatMoney(
                  data.tongDoanhThu
                )
              }
            />
          </>
        );
      }


      return (
        <>
          <Summary
            title="Tổng doanh thu vé"
            value={
              formatMoney(
                data.tongDoanhThu
              )
            }
          />

          <Summary
            title="Tổng số vé bán"
            value={
              `${data.tongSoVe} vé`
            }
          />

          <Summary
            title="Khoảng thời gian"
            value={
              `${formatDate(
                tuNgay
              )} - ${formatDate(
                denNgay
              )}`
            }
          />
        </>
      );
    };


  return (

    <div className="statistics-page">


      <div className="statistics-header">

        <h1>
          Báo cáo thống kê
        </h1>

        <p>
          Theo dõi doanh thu,
          phim và chương trình
          khuyến mại.
        </p>

      </div>


      <div className="statistics-tabs">

        <Tab
          active={
            loaiThongKe
            === "ve"
          }
          text="Doanh thu vé"
          onClick={() =>
            changeTab("ve")
          }
        />

        <Tab
          active={
            loaiThongKe
            === "combo"
          }
          text="Bắp nước / Combo"
          onClick={() =>
            changeTab(
              "combo"
            )
          }
        />

        <Tab
          active={
            loaiThongKe
            === "phim"
          }
          text="Theo phim"
          onClick={() =>
            changeTab(
              "phim"
            )
          }
        />

        <Tab
          active={
            loaiThongKe
            ===
            "khuyen-mai"
          }
          text="Khuyến mại"
          onClick={() =>
            changeTab(
              "khuyen-mai"
            )
          }
        />

      </div>


      <div className="statistics-filter">


        <div className="filter-group">

          <label>
            Từ ngày
          </label>

          <input
            type="date"
            value={tuNgay}
            onChange={(e) =>
              setTuNgay(
                e.target.value
              )
            }
          />

        </div>


        <div className="filter-group">

          <label>
            Đến ngày
          </label>

          <input
            type="date"
            min={tuNgay}
            value={denNgay}
            onChange={(e) =>
              setDenNgay(
                e.target.value
              )
            }
          />

        </div>


        {loaiThongKe ===
          "phim" && (

          <div className="filter-group">

            <label>
              Phim
            </label>

            <select
              value={maPhim}
              onChange={(e) =>
                setMaPhim(
                  e.target.value
                )
              }
            >

              <option value="">
                Tất cả phim
              </option>

              {dsPhim.map(
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
        )}


        {loaiThongKe ===
          "khuyen-mai" && (

          <div className="filter-group">

            <label>
              Chương trình
            </label>

            <select
              value={maKM}
              onChange={(e) =>
                setMaKM(
                  e.target.value
                )
              }
            >

              <option value="">
                Tất cả khuyến mại
              </option>

              {dsKhuyenMai.map(
                (item) => (

                  <option
                    key={
                      item.maKM
                    }
                    value={
                      item.maKM
                    }
                  >
                    {item.tenKM}
                  </option>

                )
              )}

            </select>

          </div>
        )}


        <button
          type="button"
          className="statistics-button"
          disabled={loading}
          onClick={
            handleThongKe
          }
        >

          {loading
            ? "Đang thống kê..."
            : "Xem thống kê"}

        </button>

      </div>


      {error && (

        <div className="statistics-error">
          {error}
        </div>

      )}


      {data && (

        <div ref={reportRef}>

          <div className="statistics-summary">

            {renderSummary()}

          </div>


          {!data.coDuLieu ? (

            <div className="statistics-empty">

              Không có dữ liệu
              trong khoảng thời gian này.

            </div>

          ) : (

            <>

              <div className="statistics-chart-box">

                <h2>
                  Biểu đồ thống kê
                </h2>

                <div className="statistics-chart">

                  <Bar
                    data={
                      getChartData()
                    }
                    options={
                      chartOptions
                    }
                  />

                </div>

              </div>


              <StatisticsTable
                type={
                  loaiThongKe
                }
                data={
                  data
                }
                formatMoney={
                  formatMoney
                }
                formatDate={
                  formatDate
                }
              />

            </>
          )}

        </div>
      )}


      {data?.coDuLieu && (

        <div className="statistics-export">

          <button
            type="button"
            onClick={
              exportExcel
            }
          >
            Xuất Excel
          </button>


          <button
            type="button"
            onClick={
              exportPDF
            }
          >
            Xuất PDF
          </button>

        </div>
      )}

    </div>
  );
}


function Tab({
  active,
  text,
  onClick,
}) {

  return (

    <button
      type="button"
      className={
        active
          ? "active"
          : ""
      }
      onClick={
        onClick
      }
    >
      {text}
    </button>

  );
}


function Summary({
  title,
  value,
}) {

  return (

    <div className="summary-card">

      <span>
        {title}
      </span>

      <strong>
        {value}
      </strong>

    </div>
  );
}


function StatisticsTable({
  type,
  data,
  formatMoney,
  formatDate,
}) {

  if (
    type === "combo"
  ) {

    return (

      <TableBox title="Chi tiết doanh thu combo">

        <tr>
          <th>Mã combo</th>
          <th>Tên combo</th>
          <th>Số lượng</th>
          <th>Doanh thu</th>
        </tr>

        {data.chiTiet.map(
          (item) => (

            <tr key={item.maCombo}>

              <td>{item.maCombo}</td>

              <td>{item.tenCombo}</td>

              <td>{item.soLuong}</td>

              <td>
                {formatMoney(
                  item.doanhThu
                )}
              </td>

            </tr>

          )
        )}

      </TableBox>
    );
  }


  if (
    type === "phim"
  ) {

    return (

      <TableBox title="Thống kê theo phim">

        <tr>
          <th>Mã phim</th>
          <th>Tên phim</th>
          <th>Số vé</th>
          <th>Doanh thu</th>
          <th>Tổng chỗ</th>
          <th>Tỷ lệ lấp ghế</th>
        </tr>

        {data.chiTiet.map(
          (item) => (

            <tr key={item.maPhim}>

              <td>{item.maPhim}</td>

              <td>{item.tenPhim}</td>

              <td>{item.soVe}</td>

              <td>
                {formatMoney(
                  item.doanhThu
                )}
              </td>

              <td>{item.tongCho}</td>

              <td>
                {item.tyLeLapGhe}%
              </td>

            </tr>

          )
        )}

      </TableBox>
    );
  }


  if (
    type ===
    "khuyen-mai"
  ) {

    return (

      <TableBox title="Thống kê chương trình khuyến mại">

        <tr>
          <th>Mã KM</th>
          <th>Tên chương trình</th>
          <th>Lượt sử dụng</th>
          <th>Tiền giảm</th>
          <th>Doanh thu</th>
        </tr>

        {data.chiTiet.map(
          (item) => (

            <tr key={item.maKM}>

              <td>{item.maKM}</td>

              <td>{item.tenKM}</td>

              <td>
                {item.soLuotSuDung}
              </td>

              <td>
                {formatMoney(
                  item.tongTienGiam
                )}
              </td>

              <td>
                {formatMoney(
                  item.doanhThu
                )}
              </td>

            </tr>

          )
        )}

      </TableBox>
    );
  }


  return (

    <TableBox title="Chi tiết doanh thu theo ngày">

      <tr>
        <th>Ngày</th>
        <th>Số vé</th>
        <th>Doanh thu</th>
      </tr>

      {data.theoNgay.map(
        (item) => (

          <tr key={item.ngay}>

            <td>
              {formatDate(
                item.ngay
              )}
            </td>

            <td>
              {item.soVe}
            </td>

            <td>
              {formatMoney(
                item.doanhThu
              )}
            </td>

          </tr>

        )
      )}

    </TableBox>
  );
}


function TableBox({
  title,
  children,
}) {

  return (

    <div className="statistics-table-box">

      <h2>
        {title}
      </h2>

      <div className="statistics-table-wrapper">

        <table className="statistics-table">

          <tbody>
            {children}
          </tbody>

        </table>

      </div>

    </div>
  );
}


export default ThongKe;