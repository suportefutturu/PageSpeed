import {
  flexRender,
  getCoreRowModel,
  useReactTable,
} from "@tanstack/react-table";
import { FC } from "react";

interface ITable {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  data: any;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  columns: any;
}
const Table: FC<ITable> = (props) => {
  const { data, columns } = props;
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    enableSorting: true,
  });
  return (
    <table className="wp-list-table widefat fixed striped table-view-list">
      <thead className="thead">
        {table.getHeaderGroups().map((headerGroup) => (
          <tr key={headerGroup.id} className="tr">
            {headerGroup.headers.map((header) => {
              return (
                <th
                  key={header.id}
                  scope="col"
                  className={`manage-column column-${header.id}`}
                >
                  {header.isPlaceholder
                    ? null
                    : flexRender(
                        header.column.columnDef.header,
                        header.getContext()
                      )}
                </th>
              );
            })}
          </tr>
        ))}
      </thead>
      <tbody className="tbody">
        {table.getRowModel().rows.map((row) => (
          <tr key={row.id} className="tr">
            {row.getVisibleCells().map((cell) => (
              <td key={cell.id} className="td">
                {flexRender(cell.column.columnDef.cell, cell.getContext())}
              </td>
            ))}
          </tr>
        ))}
      </tbody>
    </table>
  );
};

export default Table;
