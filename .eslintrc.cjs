module.exports = {
  root: true,
  env: {
    browser: true,
    es2021: true,
  },
  parser: "vue-eslint-parser",
  parserOptions: {
    parser: "@typescript-eslint/parser",
    ecmaVersion: "latest",
    sourceType: "module",
    extraFileExtensions: [".vue"],
  },
  extends: [
    "eslint:recommended",
    "plugin:vue/vue3-essential",
    "plugin:@typescript-eslint/recommended",
  ],
  plugins: ["@typescript-eslint", "unused-imports"],
  rules: {
    // Cho phép tên component 1 từ trong Vue (tùy dự án)
    "vue/multi-word-component-names": "off",
    // Xóa import thừa
    "unused-imports/no-unused-imports": "warn",
    "unused-imports/no-unused-vars": [
      "warn",
      {
        vars: "all",
        args: "after-used",
        ignoreRestSiblings: true,
        varsIgnorePattern: "^_",
        argsIgnorePattern: "^_",
      },
    ],
    // Giảm cảnh báo any
    "@typescript-eslint/no-explicit-any": "off",
    // Cho phép non-null assertion nếu cần
    "@typescript-eslint/no-non-null-assertion": "off",
    // Tắt các rule style HTML để tập trung vào lỗi logic/unused
    "vue/html-indent": "off",
    "vue/max-attributes-per-line": "off",
    "vue/singleline-html-element-content-newline": "off",
    "vue/multiline-html-element-content-newline": "off",
    "vue/html-self-closing": "off",
    "vue/attributes-order": "off",
    "vue/first-attribute-linebreak": "off",
    "vue/html-closing-bracket-newline": "off",
  },
};

