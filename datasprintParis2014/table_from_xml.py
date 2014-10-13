#parse xml code of file "napa_index_by_sector.xml" 
#(starting from the first project, at line 72) 
#to turn it into a csv table. 
#Output file needs manual cleaning

import re
import sys

xml_line = re.compile(r'<text top="(\d+)" left="(\d+)" .*>(.+?)<')

#~ lefts = [141, 269, 313, 571, 581, 696, 712]
#~ lefts = [141, 260, 312, 570, 580, 690, 710]
left_vals = {141:1, 260:2, 312:3, 500:4, 580:5, 690:6}


table = {}
raw = 0
n_raws = 0
leftvals = {}
fo =  open(sys.argv[2], 'w')

def get_col(n):
	if n < 140 or n == 740 or n == 749: return 0
	for i in reversed(sorted(left_vals)):
		if n >= i:
			#~ print 'left=%d: returning col=%d' %(i, left_vals[i]) 
			return left_vals[i]

	
def merge_raws(table):
	for raw in sorted(table):
		if raw > 1 and raw < len(table)-2 and (len(table[raw]) < 2 or table[raw][2] == '' or table[raw][2] == ' '):
			for c in table[raw]:
				#~ print table[raw]
				if table[raw][c] != '' and table[raw][c] != ' ':
					table[raw+1][c] = table[raw][c] + table[raw+1][c]
			del table[raw]		

def write_table(table):
	print 'writing %d raws' %len(table)
#~	print leftvals
	
	for raw in table:
		s = str(raw)
		for l in sorted(left_vals.values()):
			s += '\t'
			if l in table[raw]: s += table[raw][l] 
		fo.write(s + '\n')

def write_raw(table, raw):
	s = str(raw)
	for l in sorted(left_vals.values()):
		s += '\t'
		if l in table[raw]: s += table[raw][l] 
	fo.write(s + '\n')

line = 'ciao\n'
l = 0
with open(sys.argv[1], 'r') as f:
	while line:
		l += 1
		line = f.readline()
		m = xml_line.match(line)
		if m:
			top = m.group(1)
			left = int(m.group(2))
			content = m.group(3)
			col = get_col(left)
			
			if col != 0:	
				if col == 1 and (raw < 1 or table[raw][2] != ''):
					raw += 1
					table[raw] = {}
					for k in left_vals.values(): table[raw][k] = ''
					
					table[raw][col] = content
					
					if 'COUNTRY' in content:
						for i in range(11): f.readline()
						
				else:
					if raw == 0: print line
					elif content == 'COUNTRY Order ': 
						table[raw][col] += '\n\t'
						for i in range(11): f.readline()	
					
					else:	
						if col in table[raw]:
							table[raw][col] += content
						else:
							table[raw][col] = content
		n_raws =raw
	print '%d lines read' %l
	#~ merge_raws(table)
	
	write_table(table)
