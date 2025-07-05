import fs from "node:fs";
import { parse } from "tree-parse";
import iconv from "iconv-lite";

// 讀原始 bytes
const buf = fs.readFileSync("tree.txt");

// 把 CP950 → UTF-8 字串
var txt = iconv.decode(buf, "cp950"); 

// 把 Windows 的 ASCII 標記轉成 tree-parse 要的 Unicode
txt = txt
  .replace(/\+---/g, "├──")
  .replace(/\\---/g, "└──")
  .replace(/\|   /g, "│   ");

const obj = parse(txt);
fs.writeFileSync("tree.json", JSON.stringify(obj, null, 2));
