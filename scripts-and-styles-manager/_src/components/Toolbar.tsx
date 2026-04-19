import { ChangeEvent, ChangeEventHandler, FC } from "react";

interface IToolbar {
  handlePageChange: ChangeEventHandler<HTMLSelectElement>;
  selectedPage: number | undefined;
  pages: { title: string; id: number }[];
  handleSearchValueChange: (e: ChangeEvent<HTMLInputElement>) => void;
  searchValue: string;
  handleSearchChange: () => void;
}
const Toolbar: FC<IToolbar> = (props) => {
  const {
    handlePageChange,
    selectedPage,
    pages,
    searchValue,
    handleSearchValueChange,
    handleSearchChange,
  } = props;
  return (
    <div className="table-toolbar">
      <div className="pages-select">
        <label htmlFor="page-select">Select a page:</label>
        <select
          onChange={handlePageChange}
          value={selectedPage}
          className="wp-core-ui select"
          id="page-select"
        >
          {pages.map((page) => {
            return (
              <option key={page.id} value={page.id}>
                {page.title}
              </option>
            );
          })}
        </select>
      </div>
      <div className="search-container">
        <p className="search-box">
          <label className="screen-reader-text" htmlFor="post-search-input">
            Search Assets:
          </label>
          <input
            type="search"
            id="post-search-input"
            name="s"
            value={searchValue}
            onChange={handleSearchValueChange}
            placeholder="Name, type, source"
          />
          <input
            type="submit"
            id="search-submit"
            className="button"
            value="Search Assets"
            onClick={handleSearchChange}
          />
        </p>
      </div>
    </div>
  );
};

export default Toolbar;
