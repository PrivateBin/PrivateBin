export = createTypeMap;
declare function createTypeMap(match: any): {
    function: (m: any, expectation: any, message: any) => void;
    number: (m: any, expectation: any) => void;
    object: (m: any, expectation: any) => any;
    regexp: (m: any, expectation: any) => void;
    string: (m: any, expectation: any) => void;
};
