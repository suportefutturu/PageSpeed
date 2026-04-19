import { ChangeEvent, FC } from "react";

interface IPagination {
  itemsPerPage: number;
  handleItemsPerPageChange: (e: ChangeEvent<HTMLInputElement>) => void;
  totalItems: number;
  currentPage: number;
  setCurrentPage: React.Dispatch<React.SetStateAction<number>>;
  totalPages: number;
}
const Pagination: FC<IPagination> = (props) => {
  const {
    itemsPerPage,
    handleItemsPerPageChange,
    totalItems,
    currentPage,
    setCurrentPage,
    totalPages,
  } = props;
  return (
    <div className="pagination-section">
      <div className="per-page">
        <label htmlFor="items-per-page-input">Number of items per page:</label>
        <input
          type="number"
          value={itemsPerPage}
          onChange={handleItemsPerPageChange}
          id="items-per-page-input"
          className="screen-per-page"
          min={1}
          step={1}
        />
      </div>
      <div className="tablenav">
        <div className="tablenav-pages">
          <span className="displaying-num">{totalItems} items</span>
          <span className="pagination-links">
            <span
              className={`tablenav-pages-navspan button ${
                currentPage === 1 ? "disabled" : ""
              }`}
              aria-hidden="true"
              onClick={() => (currentPage > 1 ? setCurrentPage(1) : null)}
            >
              «
            </span>
            <span
              className={`tablenav-pages-navspan button ${
                currentPage === 1 ? "disabled" : ""
              }`}
              aria-hidden="true"
              onClick={() =>
                currentPage > 1 ? setCurrentPage(currentPage - 1) : null
              }
            >
              ‹
            </span>

            <span className="screen-reader-text">Current Page</span>

            <span id="table-paging" className="paging-input">
              <span className="tablenav-paging-text">
                {currentPage} of
                <span className="total-pages">{totalPages}</span>
              </span>
            </span>

            <span className="screen-reader-text">Next page</span>
            <span
              aria-hidden="true"
              onClick={() =>
                currentPage < totalPages
                  ? setCurrentPage(currentPage + 1)
                  : null
              }
              className={`tablenav-pages-navspan button ${
                currentPage === totalPages ? "disabled" : ""
              }`}
            >
              ›
            </span>

            <span
              className={`last-page button ${
                currentPage === totalPages ? "disabled" : ""
              }`}
              onClick={() =>
                currentPage < totalPages ? setCurrentPage(totalPages) : null
              }
            >
              <span className="screen-reader-text">Last page</span>
              <span aria-hidden="true">»</span>
            </span>
          </span>
        </div>
      </div>
    </div>
  );
};

export default Pagination;
