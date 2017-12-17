
all: kanji_lister

kanji_lister: main.cpp sqlite3.o cJSON.o
	c++ main.cpp sqlite3.o cJSON.o -lpthread -ldl -o kanji_lister