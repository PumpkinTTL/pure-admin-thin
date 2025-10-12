/**
 * 文本处理工具函数
 */

/**
 * Markdown 标记正则表达式
 * 用于移除 Markdown 格式，提取纯文本
 */
export const MARKDOWN_REGEX = /(?:!\[(.*?)\]\((.*?)\))|(?:\[(.*?)\]\((.*?)\))|(?:\*\*(.*?)\*\*)|(?:\*(.*?)\*)|(?:__(.*?)__)|(?:_(.*?)_)|(?:~~(.*?)~~)|(?:`(.*?)`)|(?:```([\s\S]*?)```)|(?:#+ )|(?:- )|(?:\d+\. )|(?:\|)|(?:-{3,})|(?:>{1,})/g;

/**
 * 移除文本中的 Markdown 标记
 * @param text 包含 Markdown 格式的文本
 * @returns 纯文本内容
 */
export const stripMarkdown = (text: string): string => {
  if (!text) return '';
  return text.replace(MARKDOWN_REGEX, '$1$3$5$6$7$8$9$10$11');
};

/**
 * 计算文本字数（移除空格后）
 * @param text 文本内容
 * @returns 字数
 */
export const countWords = (text: string): number => {
  if (!text) return 0;
  const plainText = stripMarkdown(text);
  return plainText.replace(/\s+/g, '').length;
};

/**
 * 计算阅读时长（分钟）
 * @param text 文本内容
 * @param wordsPerMinute 每分钟阅读字数，默认300
 * @returns 阅读时长（分钟）
 */
export const calculateReadTime = (text: string, wordsPerMinute: number = 300): number => {
  const wordCount = countWords(text);
  return Math.max(1, Math.ceil(wordCount / wordsPerMinute));
};

/**
 * 生成文章摘要
 * @param content 文章内容
 * @param maxLength 最大长度，默认100
 * @returns 摘要文本
 */
export const generateSummary = (content: string, maxLength: number = 100): string => {
  if (!content) return '';
  
  const plainText = stripMarkdown(content).trim();
  
  if (plainText.length === 0) return '';
  
  // 提取第一段落或前maxLength个字符
  const firstParagraph = plainText.split('\n\n')[0];
  return firstParagraph.length > maxLength
    ? firstParagraph.substring(0, maxLength) + '...'
    : firstParagraph;
};

/**
 * 截断文本
 * @param text 文本内容
 * @param maxLength 最大长度
 * @param suffix 后缀，默认'...'
 * @returns 截断后的文本
 */
export const truncateText = (text: string, maxLength: number, suffix: string = '...'): string => {
  if (!text || text.length <= maxLength) return text;
  return text.substring(0, maxLength) + suffix;
};

