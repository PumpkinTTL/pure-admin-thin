// @ts-check

/** @type {import("@commitlint/types").UserConfig} */
export default {
  ignores: [commit => commit.includes("init")],
  extends: ["@commitlint/config-conventional"],
  rules: {
    "body-leading-blank": [2, "always"],
    "footer-leading-blank": [1, "always"],
    "header-max-length": [2, "always", 108],
    "subject-empty": [0], // 改为0，不强制要求主题
    "type-empty": [0], // 改为0，不强制要求类型
    "type-enum": [
      0, // 改为0，不强制限制类型
      "always",
      [
        "feat",
        "fix",
        "perf",
        "style",
        "docs",
        "test",
        "refactor",
        "build",
        "ci",
        "chore",
        "revert",
        "wip",
        "workflow",
        "types",
        "release"
      ]
    ]
  }
};
