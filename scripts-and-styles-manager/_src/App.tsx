import React, { useCallback, useEffect, useState } from "react";
import "./App.css";
import { createColumnHelper } from "@tanstack/react-table";
import Table from "./components/Table";
import Pagination from "./components/Pagination";
import Toolbar from "./components/Toolbar";
import { apiUrl, nonce } from "./config";
import axios from "axios";

declare global {
  interface Window {
    wpApiSettings: { nonce: string; root: string };
  }
}

function App() {
  const [data, setData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [pages, setPages] = useState<{ title: string; id: number }[]>([]);
  const [selectedPage, setSelectedPage] = useState<number>();

  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(0);
  const [itemsPerPage, setItemsPerPage] = useState(10);
  const [totalItems, setTotalItems] = useState(1);
  const [searchValue, setSearchValue] = useState("");
  const [search, setSearch] = useState("");

  const copyToClipBoard = (value: string) => {
    navigator.clipboard.writeText(value);
  };

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const columnHelper = createColumnHelper<any>();
  const columns = [
    columnHelper.accessor("handle", {
      cell: (info) => <div className="cell-wrapper">{info.getValue()}</div>,
      header: () => <span>Asset name</span>,
    }),
    columnHelper.accessor((row) => row.type, {
      id: "type",
      cell: (info) => (
        <div className="cell-wrapper">
          <span>{info.getValue()}</span>
        </div>
      ),
      header: () => <span>Type</span>,
    }),
    columnHelper.accessor("src", {
      header: () => "Source",
      cell: (info) => (
        <div className="cell-wrapper">
          <div className="src-wrapper">
            <span
              onClick={() => copyToClipBoard(info.getValue())}
              title="Click to copy path to clipboard"
            >
              {info.renderValue()}
            </span>
          </div>
        </div>
      ),
    }),
    columnHelper.accessor("enabled", {
      header: () => <span>Enabled</span>,
      cell: ({ row }) => {
        return (
          <div className="cell-wrapper">
            <label className="switch">
              <input
                type="checkbox"
                checked={row.original.enabled}
                onChange={(e) =>
                  handleToggle(
                    row.original.type,
                    row.original.handle,
                    e.target.checked
                  )
                }
              />
              <span className="slider round"></span>
            </label>
          </div>
        );
      },
      enableSorting: true,
    }),
  ];

  const fetchAssets = useCallback(
    (page: number) => {
      setLoading(true);
      axios
        .get(
          `${apiUrl}/assets?page_id=${page}&page=${currentPage}&per_page=${itemsPerPage}&search=${search}`,
          {
            headers: {
              "Content-Type": "application/json",
              "X-WP-Nonce": nonce,
            },
          }
        )
        .then((assets) => {
          setData(assets.data.data);
          setTotalPages(assets.data.meta.total_pages);
          setTotalItems(assets.data.meta.total_items);
          setLoading(false);
        })
        .catch(() => {
          setLoading(false);
        });
    },
    [currentPage, itemsPerPage, search]
  );

  const fetchPages = useCallback(() => {
    axios
      .get(`${apiUrl}/pages`, {
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": nonce,
        },
      })
      .then((data) => {
        setPages(data.data);
        if (data.data.length > 0) {
          setSelectedPage(data.data[0].id);
        }
      })
      .catch((error) => console.error("Error fetching assets:", error));
  }, []);

  useEffect(() => {
    fetchPages();
  }, [fetchPages]);

  useEffect(() => {
    if (selectedPage) {
      fetchAssets(selectedPage);
    }
  }, [fetchAssets, selectedPage]);

  const toggleAsset = async (
    pageId: number,
    assetType: string,
    handle: string,
    enabled: boolean
  ) => {
    await axios
      .post(
        `${apiUrl}/toggle-asset`,
        {
          page_id: pageId,
          asset_type: assetType,
          handle,
          enabled,
        },
        {
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": nonce,
          },
        }
      )
      .then((res) => {
        if (res.status == 200) {
          if (res.data.success) {
            console.log("Asset updated successfully");
          }
        }
      })
      .catch((err) => console.error(err));
  };

  const handleToggle = async (
    type: string,
    handle: string,
    isEnabled: boolean
  ) => {
    try {
      if (selectedPage) {
        await toggleAsset(selectedPage, type, handle, isEnabled);
        fetchAssets(selectedPage);
      }
    } catch (error) {
      console.error(error);
    }
  };

  const handlePageChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    setSelectedPage(Number(e.target.value));
  };

  const handleItemsPerPageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setItemsPerPage(Number(e.target.value));
  };

  const handleSearchValueChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setSearchValue(e.target.value);
  };

  const handleSearchChange = () => {
    setSearch(searchValue);
  };
  return (
    <div className="content-wrapper">
      <h1 className="wrap wp-heading-inline">Scripts And Styles Manager</h1>
      <h4>
        Manage the scripts and styles loaded on your WordPress pages. Improve
        your page performance by toggling off unnecessary assets.
      </h4>

      {/* Toolbar */}
      <Toolbar
        handlePageChange={handlePageChange}
        selectedPage={selectedPage}
        pages={pages}
        handleSearchValueChange={handleSearchValueChange}
        searchValue={searchValue}
        handleSearchChange={handleSearchChange}
      />
      {data.length === 0 && !loading ? (
        <h4 className="no-results">
          No scripts or styles detected on this page
        </h4>
      ) : (
        <>
          {/* Data table */}
          <Table data={data} columns={columns} />
          {/* Pagination */}
          <Pagination
            itemsPerPage={itemsPerPage}
            handleItemsPerPageChange={handleItemsPerPageChange}
            totalItems={totalItems}
            currentPage={currentPage}
            setCurrentPage={setCurrentPage}
            totalPages={totalPages}
          />
        </>
      )}
    </div>
  );
}

export default App;
