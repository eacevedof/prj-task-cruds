export const DocumentationTestCaseEnum = Object.freeze({
    OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN: "OK_AS_SUCCESS_BY_DOCUMENTATION_WITHOUT_TOKEN",
    OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN: "OK_AS_SUCCESS_BY_CHANGELOG_WITHOUT_TOKEN",
});

export type DocumentationTestCase = typeof DocumentationTestCaseEnum[keyof typeof DocumentationTestCaseEnum];
