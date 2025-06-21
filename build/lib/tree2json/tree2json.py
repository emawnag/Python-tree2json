import re
import json

class Tree2Json:
    def __init__(self, mode="auto"):
        self.mode = mode
        self.nodes = []
        self.root = {
            "level": 0,
            "type": "dir",
            "name": ".",
            "description": "",
            "child": []
        }

    def find_branch_pos(self, line):
        match = re.search(r'[├└]──', line)
        return match.start() if match else -1

    def compute_level(self, pos):
        if pos < 0:
            return None
        if self.mode == "auto":
            if pos % 4 == 0:
                return pos // 4
            elif pos % 3 == 0:
                return pos // 3
            else:
                return round(pos / 4 if pos % 4 < pos % 3 else pos / 3)
        elif self.mode == "step3":
            return pos // 3
        elif self.mode == "step4":
            return pos // 4
        else:
            raise ValueError("mode must be 'auto', 'step3', or 'step4'")

    def parse_lines(self, tree_lines):
        self.nodes = []

        for line in tree_lines:
            pos = self.find_branch_pos(line)
            if pos == -1:
                continue
            level = self.compute_level(pos) + 1  # +1: 根目录为 level 0
            name = line[pos + 3:].strip()
            node = {
                "level": level,
                "type": "file" if '.' in name else "dir",
                "name": name,
                "description": "",
                "child": []
            }
            self.nodes.append(node)

    def build_tree(self):
        stack = [self.root]
        for node in self.nodes:
            # 回退到正确的目录层级，跳过 file
            while len(stack) > 1 and (stack[-1]["level"] >= node["level"] or stack[-1]["type"] != "dir"):
                stack.pop()

            parent = stack[-1]
            parent["child"].append(node)

            # 只有是目录才入栈（file 不能作为父节点）
            if node["type"] == "dir":
                stack.append(node)


    def from_string(self, tree_str):
        lines = tree_str.strip().splitlines()
        self.parse_lines(lines)
        self.build_tree()

    def to_dict(self):
        return self.root

    def to_json(self, path=None):
        json_str = json.dumps(self.root, indent=4, ensure_ascii=False)

        if path:
            with open(path, "w", encoding="utf-8") as f:
                f.write(json_str)

        return json_str

