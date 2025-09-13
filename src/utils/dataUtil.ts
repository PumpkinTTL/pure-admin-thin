/**
 * 比较两个对象是否相等
 * @param obj1 第一个对象
 * @param obj2 第二个对象
 * @param ignoreKeys 需要忽略比较的key数组
 * @returns boolean 是否相等
 */
export function objectIsEqual(
  obj1: any,
  obj2: any,
  ignoreKeys: string[] = []
): boolean {
  if (obj1 === obj2) return true;

  if (
    typeof obj1 !== "object" ||
    obj1 === null ||
    typeof obj2 !== "object" ||
    obj2 === null
  ) {
    return false;
  }

  const keys1 = Object.keys(obj1).filter(key => !ignoreKeys.includes(key));
  const keys2 = Object.keys(obj2).filter(key => !ignoreKeys.includes(key));

  if (keys1.length !== keys2.length) return false;

  for (const key of keys1) {
    if (!keys2.includes(key)) return false;

    const val1 = obj1[key];
    const val2 = obj2[key];

    if (typeof val1 === "object" && typeof val2 === "object") {
      if (!objectIsEqual(val1, val2, ignoreKeys)) return false;
    } else if (val1 !== val2) {
      return false;
    }
  }

  return true;
}

/**

 * 生成数字编号（单个返回字符串，多个返回数组）
 * @param count 生成数量
 * @param length 编号位数
 * @param prefix 可选前缀
 */
export function generateSerialNumbers(
  count: number,
  length: number,
  prefix?: string
): number | number[] | string | string[] {
  // 参数检查
  if (count < 1 || length < 1) throw new Error("参数必须大于0");

  // 生成单个编号（首位不为0）
  const makeNumber = () => {
    const first = Math.floor(Math.random() * 9) + 1; // 1-9
    const rest = Array.from(
      { length: length - 1 },
      () => Math.floor(Math.random() * 10) // 0-9
    ).join("");
    return `${prefix || ""}${first}${rest}`;
  };

  // 批量生成有序编号
  if (count > 1) {
    const start =
      Math.floor(Math.random() * 9 * Math.pow(10, length - 1)) +
      Math.pow(10, length - 1);
    return Array.from(
      { length: count },
      (_, i) => `${prefix || ""}${start + i}`
    );
  }

  // 返回单个
  return makeNumber();
}
