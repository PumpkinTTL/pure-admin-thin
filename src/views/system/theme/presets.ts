/**
 * 主题预设配置 - 170套精美配色方案
 */

export interface ThemePreset {
  name: string
  description: string
  colors: {
    primary: string
    secondary: string
    success: string
    warning: string
    danger: string
    info: string
  }
}

const c = (primary: string, secondary: string, success: string, warning: string, danger: string, info: string) => ({
  primary, secondary, success, warning, danger, info
})

export const themePresets: ThemePreset[] = [
  // 经典框架 (1-5)
  { name: 'Element 经典蓝', description: 'Element Plus 官方配色', colors: c('#409EFF', '#909399', '#67C23A', '#E6A23C', '#F56C6C', '#909399') },
  { name: 'Ant Design', description: '蚂蚁金服设计语言', colors: c('#1890FF', '#597EF7', '#52C41A', '#FAAD14', '#F5222D', '#13C2C2') },
  { name: 'Material Design', description: 'Google Material', colors: c('#2196F3', '#90CAF9', '#4CAF50', '#FF9800', '#F44336', '#00BCD4') },
  { name: 'Bootstrap', description: 'Bootstrap框架', colors: c('#0D6EFD', '#6C757D', '#198754', '#FFC107', '#DC3545', '#0DCAF0') },
  { name: 'Tailwind', description: 'Tailwind CSS', colors: c('#3B82F6', '#6B7280', '#10B981', '#F59E0B', '#EF4444', '#06B6D4') },

  // 科技未来 (6-25)
  { name: '赛博朋克', description: '霓虹未来感', colors: c('#00F5FF', '#BD00FF', '#00FF94', '#FFD700', '#FF006E', '#00D9FF') },
  { name: 'GitHub暗夜', description: '程序员最爱', colors: c('#58A6FF', '#8B949E', '#3FB950', '#D29922', '#F85149', '#79C0FF') },
  { name: '电子蓝', description: '科技蓝光', colors: c('#0066FF', '#0099FF', '#00CC66', '#FFAA00', '#FF3366', '#00CCFF') },
  { name: '黑客帝国', description: '经典矩阵绿', colors: c('#00FF41', '#00AA00', '#00FF88', '#FFD700', '#FF0055', '#00FFCC') },
  { name: '星际深空', description: '深邃宇宙', colors: c('#4169E1', '#6A5ACD', '#32CD32', '#FFD700', '#DC143C', '#1E90FF') },
  { name: '量子紫', description: '科幻紫色', colors: c('#9370DB', '#BA55D3', '#00FA9A', '#FFD700', '#FF69B4', '#87CEEB') },
  { name: '激光红', description: '炫目激光', colors: c('#FF0080', '#FF1493', '#00FF00', '#FFFF00', '#FF0000', '#00FFFF') },
  { name: '钴蓝电路', description: '电路板蓝', colors: c('#0047AB', '#4682B4', '#228B22', '#FFA500', '#DC143C', '#00CED1') },
  { name: '液态银', description: '未来感银', colors: c('#708090', '#A9A9A9', '#3CB371', '#DAA520', '#B22222', '#5F9EA0') },
  { name: '霓虹粉蓝', description: '霓虹双色', colors: c('#FF10F0', '#10F0FF', '#10FF10', '#FFFF10', '#FF1010', '#10FFFF') },
  { name: '数码紫', description: '数字紫调', colors: c('#7B68EE', '#9370DB', '#00FA9A', '#FFD700', '#FF1493', '#48D1CC') },
  { name: '等离子蓝', description: '等离子体', colors: c('#1E3A8A', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#06B6D4') },
  { name: '原子绿', description: '原子能量', colors: c('#00FF7F', '#00FA9A', '#32CD32', '#FFD700', '#FF4500', '#00CED1') },
  { name: '光速蓝', description: '光速疾驰', colors: c('#00BFFF', '#1E90FF', '#00FF7F', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '暗物质', description: '神秘暗物质', colors: c('#1a1a2e', '#16213e', '#0f4c75', '#3282b8', '#e94560', '#1a1a2e') },
  { name: '星云紫', description: '星云紫色', colors: c('#6A0DAD', '#7B2CBF', '#10B981', '#F59E0B', '#EF4444', '#06B6D4') },
  { name: '离子橙', description: '离子橙色', colors: c('#FF5722', '#FF6F00', '#4CAF50', '#FFC107', '#F44336', '#00BCD4') },
  { name: '脉冲粉', description: '能量脉冲', colors: c('#FF007F', '#FF1493', '#00FF7F', '#FFD700', '#DC143C', '#00CED1') },
  { name: '光子黄', description: '光子黄色', colors: c('#FFEB3B', '#FFC107', '#4CAF50', '#FF9800', '#F44336', '#00BCD4') },
  { name: '中子蓝', description: '中子蓝色', colors: c('#0288D1', '#039BE5', '#4CAF50', '#FFC107', '#F44336', '#00BCD4') },

  // 自然清新 (26-50)
  { name: '森林绿', description: '自然森林', colors: c('#228B22', '#2E8B57', '#32CD32', '#FFD700', '#DC143C', '#20B2AA') },
  { name: '薄荷绿', description: '清凉薄荷', colors: c('#3EB489', '#48D1CC', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '抹茶绿', description: '温和抹茶', colors: c('#88C559', '#9ACD32', '#7CFC00', '#FFD700', '#FF6347', '#20B2AA') },
  { name: '竹林翠', description: '翠绿竹林', colors: c('#00A86B', '#3CB371', '#00FF7F', '#FFD700', '#DC143C', '#20B2AA') },
  { name: '湖水蓝', description: '清澈湖水', colors: c('#5F9EA0', '#48D1CC', '#00CED1', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '海洋蓝', description: '深邃海洋', colors: c('#1E90FF', '#4169E1', '#00CED1', '#FFD700', '#FF6347', '#00BFFF') },
  { name: '天际蓝', description: '晴空万里', colors: c('#87CEEB', '#4682B4', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '珊瑚橙', description: '珊瑚礁橙', colors: c('#FF7F50', '#FFA07A', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '晨曦粉', description: '清晨霞光', colors: c('#FFB6C1', '#FFC0CB', '#98FB98', '#FFD700', '#FF69B4', '#87CEEB') },
  { name: '柠檬黄', description: '清新柠檬', colors: c('#FFD700', '#FFA500', '#00FF00', '#FFFF00', '#FF4500', '#00CED1') },
  { name: '青草绿', description: '嫩绿青草', colors: c('#7CFC00', '#ADFF2F', '#00FF00', '#FFD700', '#FF6347', '#00CED1') },
  { name: '向日葵', description: '明亮向日葵', colors: c('#FFB90F', '#FFC125', '#00CD00', '#FFFF00', '#FF6347', '#00CED1') },
  { name: '苹果绿', description: '清脆苹果', colors: c('#8CE600', '#9AFF02', '#00FF00', '#FFD700', '#FF4500', '#00CED1') },
  { name: '孔雀蓝', description: '华丽孔雀', colors: c('#00A5AF', '#00CED1', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '蔚蓝海岸', description: '地中海蓝', colors: c('#0080FF', '#00BFFF', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '翠鸟蓝', description: '翠鸟羽毛', colors: c('#00CED1', '#20B2AA', '#00FA9A', '#FFD700', '#FF6347', '#48D1CC') },
  { name: '雨林绿', description: '热带雨林', colors: c('#006400', '#228B22', '#00FF00', '#FFD700', '#8B0000', '#008B8B') },
  { name: '沙滩金', description: '温暖沙滩', colors: c('#F4A460', '#DEB887', '#3CB371', '#FFD700', '#CD5C5C', '#5F9EA0') },
  { name: '夕阳红', description: '浪漫夕阳', colors: c('#FF6347', '#FF7F50', '#32CD32', '#FFD700', '#DC143C', '#00CED1') },
  { name: '瀑布蓝', description: '瀑布飞溅', colors: c('#00CED1', '#48D1CC', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '火山红', description: '火山岩浆', colors: c('#DC143C', '#FF4500', '#228B22', '#FFD700', '#8B0000', '#4682B4') },
  { name: '冰川蓝', description: '冰川寒冰', colors: c('#B0E0E6', '#ADD8E6', '#E0FFE0', '#FFFACD', '#FFE4E1', '#F0FFFF') },
  { name: '沙漠黄', description: '沙漠金黄', colors: c('#EDC9AF', '#DEB887', '#8FBC8F', '#F0E68C', '#BC8F8F', '#ADD8E6') },
  { name: '苔藓绿', description: '苔藓深绿', colors: c('#556B2F', '#6B8E23', '#228B22', '#DAA520', '#8B0000', '#2F4F4F') },
  { name: '岩石灰', description: '岩石深灰', colors: c('#696969', '#808080', '#3CB371', '#DAA520', '#8B0000', '#708090') },

  // 浪漫优雅 (51-75)
  { name: '樱花粉', description: '温柔樱花', colors: c('#FFB7DD', '#FFC0CB', '#98FB98', '#FFD700', '#FF69B4', '#DDA0DD') },
  { name: '玫瑰红', description: '优雅玫瑰', colors: c('#FF007F', '#FF1493', '#00FF7F', '#FFD700', '#DC143C', '#BA55D3') },
  { name: '紫罗兰', description: '神秘紫罗兰', colors: c('#8A2BE2', '#9370DB', '#00FA9A', '#FFD700', '#FF1493', '#87CEEB') },
  { name: '薰衣草', description: '浪漫薰衣草', colors: c('#E6E6FA', '#DDA0DD', '#98FB98', '#FFD700', '#FF69B4', '#B0C4DE') },
  { name: '丁香紫', description: '优雅丁香', colors: c('#C8A2C8', '#DA70D6', '#98FB98', '#FFD700', '#FF69B4', '#9370DB') },
  { name: '蔷薇粉', description: '甜美蔷薇', colors: c('#FF66B2', '#FF85C1', '#7CFC00', '#FFD700', '#FF1493', '#DDA0DD') },
  { name: '桃花粉', description: '娇嫩桃花', colors: c('#FFDAB9', '#FFE4E1', '#98FB98', '#FFD700', '#FF69B4', '#FFB6C1') },
  { name: '香槟金', description: '奢华香槟', colors: c('#F7E7CE', '#DEB887', '#9ACD32', '#FFD700', '#CD5C5C', '#87CEEB') },
  { name: '玫瑰金', description: '时尚玫瑰金', colors: c('#B76E79', '#C9A0A0', '#8FBC8F', '#DAA520', '#CD5C5C', '#87CEEB') },
  { name: '丝绸粉', description: '柔滑丝绸', colors: c('#FFE4E1', '#FFF0F5', '#E0FFE0', '#FFFACD', '#FFE4E1', '#F0F8FF') },
  { name: '梦幻紫', description: '梦幻色调', colors: c('#DDA0DD', '#EE82EE', '#98FB98', '#FFD700', '#FF69B4', '#B0E0E6') },
  { name: '水晶粉', description: '透亮水晶', colors: c('#FFC1CC', '#FFD1DC', '#B4EEB4', '#FFD700', '#FF69B4', '#B0E0E6') },
  { name: '奶油色', description: '温柔奶油', colors: c('#FFFACD', '#FAFAD2', '#E0FFE0', '#FFE4B5', '#FFB6C1', '#E0FFFF') },
  { name: '象牙白', description: '优雅象牙', colors: c('#FFFFF0', '#FFF8DC', '#F0FFF0', '#FAFAD2', '#FFE4E1', '#F0F8FF') },
  { name: '珍珠白', description: '珍珠光泽', colors: c('#FAF0E6', '#FFF5EE', '#F0FFF0', '#FAFAD2', '#FFE4E1', '#F0FFFF') },
  { name: '月光蓝', description: '皎洁月光', colors: c('#B0C4DE', '#ADD8E6', '#E0FFE0', '#FFFACD', '#FFB6C1', '#AFEEEE') },
  { name: '星光银', description: '璀璨星光', colors: c('#C0C0C0', '#D3D3D3', '#90EE90', '#F0E68C', '#FFB6C1', '#B0E0E6') },
  { name: '云朵白', description: '柔软云朵', colors: c('#F5F5F5', '#FFFFFF', '#F0FFF0', '#FFFACD', '#FFE4E1', '#F0FFFF') },
  { name: '晚霞橙', description: '浪漫晚霞', colors: c('#FF8C69', '#FFA07A', '#98FB98', '#FFD700', '#FF6347', '#87CEEB') },
  { name: '郁金香', description: '郁金香粉紫', colors: c('#EE82EE', '#DA70D6', '#98FB98', '#FFD700', '#FF69B4', '#87CEEB') },
  { name: '丁香花', description: '丁香花紫', colors: c('#CDA4DE', '#D8BFD8', '#98FB98', '#FFD700', '#FF69B4', '#B0E0E6') },
  { name: '茉莉白', description: '茉莉花白', colors: c('#FFF8E7', '#FFFAF0', '#F0FFF0', '#FFFACD', '#FFE4E1', '#F0FFFF') },
  { name: '牡丹粉', description: '牡丹花粉', colors: c('#FFB3BA', '#FFC0CB', '#98FB98', '#FFD700', '#FF69B4', '#E6E6FA') },
  { name: '百合白', description: '百合纯白', colors: c('#FFFAFA', '#FFF5EE', '#F0FFF0', '#FFFACD', '#FFE4E1', '#F0FFFF') },
  { name: '兰花紫', description: '幽兰紫色', colors: c('#8E4585', '#9F5F9F', '#3CB371', '#DAA520', '#8B4789', '#87CEEB') },

  // 商务专业 (76-95)
  { name: '午夜蓝', description: '深沉商务', colors: c('#191970', '#483D8B', '#228B22', '#DAA520', '#8B0000', '#4682B4') },
  { name: '石墨黑', description: '专业石墨', colors: c('#2F4F4F', '#696969', '#3CB371', '#DAA520', '#B22222', '#708090') },
  { name: '铂金灰', description: '高端铂金', colors: c('#778899', '#A9A9A9', '#3CB371', '#DAA520', '#CD5C5C', '#5F9EA0') },
  { name: '钢铁蓝', description: '工业钢铁', colors: c('#4682B4', '#6A5ACD', '#3CB371', '#DAA520', '#B22222', '#5F9EA0') },
  { name: '海军蓝', description: '经典海军', colors: c('#000080', '#191970', '#228B22', '#FF8C00', '#8B0000', '#4682B4') },
  { name: '墨绿色', description: '深沉墨绿', colors: c('#006400', '#2E8B57', '#32CD32', '#DAA520', '#8B0000', '#20B2AA') },
  { name: '栗色', description: '稳重栗色', colors: c('#800000', '#A0522D', '#228B22', '#DAA520', '#DC143C', '#4682B4') },
  { name: '古铜色', description: '复古古铜', colors: c('#B87333', '#CD853F', '#3CB371', '#DAA520', '#CD5C5C', '#5F9EA0') },
  { name: '深灰蓝', description: '专业深灰', colors: c('#475569', '#64748B', '#059669', '#D97706', '#DC2626', '#0891B2') },
  { name: '炭灰色', description: '低调炭灰', colors: c('#36454F', '#708090', '#3CB371', '#DAA520', '#B22222', '#5F9EA0') },
  { name: '黑曜石', description: '神秘黑曜', colors: c('#1C1C1C', '#3E3E3E', '#2E8B57', '#DAA520', '#8B0000', '#4682B4') },
  { name: '青铜色', description: '古典青铜', colors: c('#8C7853', '#A0826D', '#3CB371', '#DAA520', '#8B4513', '#5F9EA0') },
  { name: '钛金属', description: '现代钛金', colors: c('#878681', '#A8A8A8', '#3CB371', '#DAA520', '#B22222', '#708090') },
  { name: '磨砂黑', description: '质感磨砂', colors: c('#28282B', '#505050', '#3CB371', '#DAA520', '#8B0000', '#696969') },
  { name: '水泥灰', description: '工业水泥', colors: c('#848482', '#A8A9AD', '#3CB371', '#DAA520', '#B22222', '#708090') },
  { name: '橡木棕', description: '橡木棕色', colors: c('#806517', '#8B7355', '#3CB371', '#DAA520', '#8B4513', '#5F9EA0') },
  { name: '胡桃木', description: '胡桃木色', colors: c('#5C4033', '#704214', '#3CB371', '#DAA520', '#8B4513', '#5F9EA0') },
  { name: '红木色', description: '红木深色', colors: c('#7E3517', '#8B4513', '#3CB371', '#DAA520', '#8B0000', '#5F9EA0') },
  { name: '黑檀木', description: '黑檀木色', colors: c('#3B2F2F', '#483C32', '#2E8B57', '#DAA520', '#8B0000', '#4682B4') },
  { name: '松木色', description: '松木浅色', colors: c('#E3C565', '#EADA52', '#9ACD32', '#FFD700', '#CD5C5C', '#87CEEB') },

  // 活力鲜艳 (96-120)
  { name: '火焰红', description: '炽热火焰', colors: c('#FF4500', '#FF6347', '#32CD32', '#FFD700', '#DC143C', '#1E90FF') },
  { name: '热情洋红', description: '热烈洋红', colors: c('#FF1493', '#FF69B4', '#00FF7F', '#FFD700', '#DC143C', '#00CED1') },
  { name: '活力橙', description: '充满活力', colors: c('#FF8C00', '#FFA500', '#32CD32', '#FFD700', '#DC143C', '#00CED1') },
  { name: '阳光黄', description: '明亮阳光', colors: c('#FFD700', '#FFA500', '#32CD32', '#FFFF00', '#FF4500', '#00CED1') },
  { name: '春草绿', description: '生机春草', colors: c('#7FFF00', '#32CD32', '#00FF00', '#FFD700', '#FF4500', '#00CED1') },
  { name: '翡翠绿', description: '宝石翡翠', colors: c('#50C878', '#3CB371', '#00FF7F', '#FFD700', '#DC143C', '#20B2AA') },
  { name: '水晶蓝', description: '透亮水晶', colors: c('#5DADE2', '#7FB3D5', '#58D68D', '#F4D03F', '#EC7063', '#48C9B0') },
  { name: '红宝石', description: '华贵红宝石', colors: c('#E0115F', '#C41E3A', '#00FA9A', '#FFD700', '#DC143C', '#4169E1') },
  { name: '蓝宝石', description: '高贵蓝宝石', colors: c('#0F52BA', '#082567', '#00FA9A', '#FFD700', '#DC143C', '#4169E1') },
  { name: '祖母绿', description: '珍贵祖母绿', colors: c('#50C878', '#00A86B', '#00FF7F', '#FFD700', '#DC143C', '#20B2AA') },
  { name: '紫水晶', description: '梦幻紫水晶', colors: c('#9966CC', '#AA66CC', '#00FA9A', '#FFD700', '#FF1493', '#87CEEB') },
  { name: '黄水晶', description: '明亮黄水晶', colors: c('#E4D00A', '#FFD700', '#00FF00', '#FFFF00', '#FF4500', '#00CED1') },
  { name: '粉水晶', description: '柔美粉水晶', colors: c('#FFE4E1', '#FFC0CB', '#98FB98', '#FFD700', '#FF69B4', '#E6E6FA') },
  { name: '碧玺绿', description: '宝石碧玺', colors: c('#00A86B', '#00C78C', '#00FF7F', '#FFD700', '#DC143C', '#00CED1') },
  { name: '琥珀色', description: '温暖琥珀', colors: c('#FFBF00', '#FF9900', '#32CD32', '#FFD700', '#DC143C', '#00CED1') },
  { name: '石榴红', description: '深红石榴', colors: c('#B22222', '#DC143C', '#228B22', '#DAA520', '#8B0000', '#4682B4') },
  { name: '松石绿', description: '松石蓝绿', colors: c('#40E0D0', '#48D1CC', '#00FA9A', '#FFD700', '#FF6347', '#7FFFD4') },
  { name: '孔雀石', description: '孔雀石绿', colors: c('#00A86B', '#2E8B57', '#00FF7F', '#FFD700', '#DC143C', '#20B2AA') },
  { name: '玛瑙红', description: '玛瑙红色', colors: c('#B22222', '#CD5C5C', '#228B22', '#DAA520', '#8B0000', '#4682B4') },
  { name: '翡翠玉', description: '翡翠玉绿', colors: c('#00A572', '#00C78C', '#00FF7F', '#FFD700', '#DC143C', '#20B2AA') },
  { name: '珊瑚玉', description: '珊瑚玉红', colors: c('#FF7F50', '#FFA07A', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '碧玉绿', description: '碧玉深绿', colors: c('#00A572', '#2E8B57', '#00FF7F', '#FFD700', '#DC143C', '#008B8B') },
  { name: '白玉色', description: '白玉温润', colors: c('#F0F8FF', '#F5F5F5', '#F0FFF0', '#FFFACD', '#FFE4E1', '#F0FFFF') },
  { name: '黑玉色', description: '黑玉深沉', colors: c('#2F4F4F', '#556B2F', '#2E8B57', '#DAA520', '#8B0000', '#708090') },
  { name: '青玉色', description: '青玉色调', colors: c('#008B8B', '#2F4F4F', '#2E8B57', '#DAA520', '#8B0000', '#5F9EA0') },

  // 莫兰迪色系 (121-140)
  { name: '雾霾蓝', description: '柔和雾霾', colors: c('#6B7B8C', '#8B9CAC', '#9FB4A8', '#E8C07D', '#D98880', '#85C1E2') },
  { name: '奶茶色', description: '温暖奶茶', colors: c('#C3A995', '#D4BFA5', '#A8BFA8', '#E8C07D', '#D98880', '#A9C5D4') },
  { name: '藕粉色', description: '温柔藕粉', colors: c('#E8B4B8', '#F5C2C7', '#B4D8B4', '#F5E3A8', '#E8A8A8', '#C2D5E8') },
  { name: '烟灰紫', description: '优雅烟灰', colors: c('#9B8FA9', '#B5A8BD', '#A8BFA8', '#E8C07D', '#D98880', '#A8B5C2') },
  { name: '豆沙绿', description: '柔和豆沙', colors: c('#A8BFA8', '#B8CFB8', '#C2D8C2', '#E8C07D', '#D98880', '#A9C5D4') },
  { name: '杏仁米', description: '温暖杏仁', colors: c('#D4C5AA', '#E8D4AA', '#B8CFAA', '#E8D4AA', '#D98880', '#C2D5E8') },
  { name: '驼色', description: '经典驼色', colors: c('#C19A6B', '#D2B48C', '#A8BFA8', '#E8C07D', '#D98880', '#A9C5D4') },
  { name: '卡其色', description: '复古卡其', colors: c('#C3B091', '#D2C29D', '#A8BFA8', '#E8D4AA', '#D98880', '#C2D5E8') },
  { name: '亚麻色', description: '自然亚麻', colors: c('#E8D4AA', '#F5E3B8', '#C2D8C2', '#F5E3A8', '#E8C2C2', '#D4E8F5') },
  { name: '燕麦色', description: '温和燕麦', colors: c('#E0D5C7', '#F0E5D7', '#D4E8D4', '#E8D4AA', '#E8C2C2', '#D4E8F5') },
  { name: '米灰色', description: '中性米灰', colors: c('#C8C4BC', '#D8D4CC', '#C2D8C2', '#E8D4AA', '#E8C2C2', '#D4E8F5') },
  { name: '浅咖色', description: '温暖浅咖', colors: c('#B8A894', '#C8B8A4', '#A8BFA8', '#E8C07D', '#D98880', '#A9C5D4') },
  { name: '浅褐色', description: '温润浅褐', colors: c('#A89284', '#B8A294', '#A8BFA8', '#E8C07D', '#D98880', '#A9C5D4') },
  { name: '粉灰色', description: '柔和粉灰', colors: c('#C8B4B8', '#D8C4C8', '#B4D8B4', '#E8D4AA', '#E8C2C2', '#C2D5E8') },
  { name: '蓝灰色', description: '冷静蓝灰', colors: c('#A8B4C8', '#B8C4D8', '#B4D8B4', '#E8D4AA', '#E8B4B4', '#C2D5E8') },
  { name: '绿灰色', description: '自然绿灰', colors: c('#A8BFB4', '#B8CFC4', '#C2D8C2', '#E8D4AA', '#E8C2C2', '#D4E8F5') },
  { name: '紫灰色', description: '优雅紫灰', colors: c('#B4A8BF', '#C4B8CF', '#B4D8B4', '#E8D4AA', '#E8B4B4', '#C2D5E8') },
  { name: '橙灰色', description: '温暖橙灰', colors: c('#BFA895', '#CFB8A5', '#A8BFA8', '#E8C07D', '#D98880', '#A9C5D4') },
  { name: '黄灰色', description: '柔和黄灰', colors: c('#C8BFA8', '#D8CEB8', '#B8CFAA', '#E8D4AA', '#E8C2C2', '#C2D5E8') },
  { name: '莫兰迪蓝', description: '高级蓝调', colors: c('#8BA3B4', '#9BB3C4', '#A8BFA8', '#E8C07D', '#D98880', '#B5C8D4') },

  // 渐变系列 (141-160)
  { name: '晨光渐变', description: '清晨光线', colors: c('#FF9A8B', '#FF6A88', '#67C23A', '#FFDAB9', '#FF6347', '#87CEEB') },
  { name: '黄昏渐变', description: '黄昏天空', colors: c('#FFA07A', '#FF7F50', '#32CD32', '#FFD700', '#DC143C', '#4169E1') },
  { name: '海洋渐变', description: '深海色彩', colors: c('#2E8B57', '#3CB371', '#00FA9A', '#FFD700', '#FF6347', '#00CED1') },
  { name: '极光渐变', description: '极光色彩', colors: c('#00FA9A', '#00CED1', '#00FF7F', '#FFD700', '#FF69B4', '#87CEEB') },
  { name: '彩虹渐变', description: '七彩彩虹', colors: c('#FF0000', '#FF7F00', '#00FF00', '#FFFF00', '#FF0000', '#0000FF') },
  { name: '日落渐变', description: '日落余晖', colors: c('#FF6B6B', '#FFE66D', '#4ECDC4', '#FFD93D', '#FF6B6B', '#95E1D3') },
  { name: '樱花渐变', description: '樱花飘落', colors: c('#FFB7C5', '#FFC0CB', '#98FB98', '#FFE4E1', '#FF69B4', '#E6E6FA') },
  { name: '薰衣草渐变', description: '薰衣草田', colors: c('#967BB6', '#C8A2C8', '#98FB98', '#FFD700', '#FF69B4', '#B0E0E6') },
  { name: '森林渐变', description: '森林深浅', colors: c('#2D5016', '#568203', '#7CFC00', '#FFD700', '#8B4513', '#2F4F4F') },
  { name: '火焰渐变', description: '火焰燃烧', colors: c('#FF4500', '#FFD700', '#32CD32', '#FFA500', '#DC143C', '#FF8C00') },
  { name: '冰雪渐变', description: '冰雪世界', colors: c('#E0F7FA', '#B2EBF2', '#C8E6C9', '#FFF9C4', '#FFCCBC', '#BBDEFB') },
  { name: '紫霞渐变', description: '紫色霞光', colors: c('#9C27B0', '#E1BEE7', '#66BB6A', '#FFD54F', '#EF5350', '#42A5F5') },
  { name: '蓝紫渐变', description: '蓝紫梦幻', colors: c('#5E35B1', '#7E57C2', '#66BB6A', '#FFCA28', '#EF5350', '#29B6F6') },
  { name: '粉蓝渐变', description: '粉蓝柔和', colors: c('#E91E63', '#2196F3', '#4CAF50', '#FFEB3B', '#F44336', '#00BCD4') },
  { name: '金红渐变', description: '金红华丽', colors: c('#FFA000', '#D84315', '#43A047', '#FFD600', '#E53935', '#039BE5') },
  { name: '绿蓝渐变', description: '绿蓝清新', colors: c('#00BFA5', '#0097A7', '#00E676', '#FFD600', '#FF5252', '#00B0FF') },
  { name: '橙粉渐变', description: '橙粉活力', colors: c('#FF6E40', '#FF4081', '#69F0AE', '#FFD740', '#FF1744', '#40C4FF') },
  { name: '黄绿渐变', description: '黄绿明亮', colors: c('#FFD600', '#76FF03', '#00E676', '#FFFF00', '#FF6E40', '#00E5FF') },
  { name: '紫粉渐变', description: '紫粉梦幻', colors: c('#E040FB', '#FF4081', '#69F0AE', '#FFD740', '#F50057', '#18FFFF') },
  { name: '青橙渐变', description: '青橙对比', colors: c('#00E5FF', '#FF6E40', '#00E676', '#FFD600', '#FF1744', '#00B0FF') },

  // 四季系列 (161-165)
  { name: '春暖花开', description: '春天嫩绿粉', colors: c('#90EE90', '#FFB6C1', '#00FF7F', '#FFD700', '#FF69B4', '#87CEEB') },
  { name: '夏日清凉', description: '夏天海蓝绿', colors: c('#00CED1', '#7CFC00', '#00FA9A', '#FFD700', '#FF6347', '#40E0D0') },
  { name: '秋日丰收', description: '秋天金黄橙', colors: c('#FF8C00', '#DAA520', '#228B22', '#FFD700', '#DC143C', '#4682B4') },
  { name: '冬日暖阳', description: '冬天暖橙蓝', colors: c('#FFA500', '#4682B4', '#228B22', '#FFD700', '#DC143C', '#87CEEB') },
  { name: '四季更替', description: '四季色彩', colors: c('#90EE90', '#FFD700', '#FF8C00', '#87CEEB', '#DC143C', '#4682B4') },

  // 节日系列 (166-170)
  { name: '圣诞红绿', description: '圣诞节配色', colors: c('#DC143C', '#228B22', '#32CD32', '#FFD700', '#B22222', '#4682B4') },
  { name: '万圣橙黑', description: '万圣节配色', colors: c('#FF8C00', '#191970', '#228B22', '#FFD700', '#8B0000', '#708090') },
  { name: '春节红金', description: '春节喜庆', colors: c('#DC143C', '#FFD700', '#228B22', '#FFA500', '#B22222', '#4682B4') },
  { name: '情人粉红', description: '情人节粉红', colors: c('#FF1493', '#FF69B4', '#FF69B4', '#FFD700', '#DC143C', '#FFB6C1') },
  { name: '中秋金黄', description: '中秋月圆', colors: c('#FFD700', '#FFA500', '#228B22', '#FFFF00', '#DC143C', '#4682B4') }
]

export const getPresetByName = (name: string): ThemePreset | undefined => {
  return themePresets.find(preset => preset.name === name)
}

export const getRandomPreset = (): ThemePreset => {
  const randomIndex = Math.floor(Math.random() * themePresets.length)
  return themePresets[randomIndex]
}

export const getPresetNames = (): string[] => {
  return themePresets.map(preset => preset.name)
}

export const getPresetsByCategory = () => {
  return {
    '经典框架': themePresets.slice(0, 5),
    '科技未来': themePresets.slice(5, 25),
    '自然清新': themePresets.slice(25, 50),
    '浪漫优雅': themePresets.slice(50, 75),
    '商务专业': themePresets.slice(75, 95),
    '活力鲜艳': themePresets.slice(95, 120),
    '莫兰迪色': themePresets.slice(120, 140),
    '渐变系列': themePresets.slice(140, 160),
    '四季节日': themePresets.slice(160, 170)
  }
}
