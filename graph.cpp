#include <iostream>
#include "graph.h"
#include "random.h"

using namespace std;


// Generate a graph of size n x n with approximately cn fraction of edges selected randomly.
// The parameter sd seeds the random number generator. Use a sd = 0 to read system time to set
// the seed for the generator

graph::graph ( int n, double cn, long sd ) : randomStream( sd )
{
	int i, j;

	// Allocate sapce for a N x N adjacency matrix

	sizeN		= n;
	adjMatrix	= new bool*[sizeN];
	for ( i = 0; i < sizeN; i++ ) adjMatrix[i] = new bool[sizeN];

	// generate a set of edge connections

	for ( i = 0; i < sizeN; i++ )
	{
		for ( j = i+1; j < sizeN; j++ )
		{
			adjMatrix[j][i] = adjMatrix[i][j] = ( bernoulli( cn ) ? true : false );
			//testing
			//if( adjMatrix[i][j] )
			 //  cout << "adjMatrix("<<i<<","<<j<<") is in the graph.\n";
		}
	}
}

// Destroy the graph

graph::~graph()
{
	delete [] adjMatrix;
}

// Count the number of 1s in a bit vector

int graph::total( bool set[], int sizeN ) const
{
	// count number of true values in a set

	int cnt = 0;
	for ( int i = 0; i < sizeN; i++ )
	{
		if ( set[i] ) cnt++;
	}
	return cnt;
}

// Generate a independent set using a greedy method. The set is return with the bits turned
// on to indicate which vertices were selected. The value setCnt contains the number of vertices
// in the independent subset.

void graph::greedySolution( bool set[], int& setCnt )
{

	int		i, j;
	bool	done;
	int*	degree;
	bool*	marked;

	// allocate space for an array to record the degree of each vertex

	degree = new int[sizeN];
	for ( i = 0; i < sizeN; i++ ) degree[i] = 0;

	// allocate and set all vertex marks to false

	marked = new bool[sizeN];
	for ( i = 0; i < sizeN; i++ ) marked[i] = false;

	// clear the set of vertices in set representing the set S

	for ( i = 0; i < sizeN; i++ ) set[i] = false;

	// compute the degree of each vertex in the graph
	
	for ( i = 0; i < sizeN; i++ )
	{
		for ( j = i+1; j < sizeN; j++ )
		{
			if ( adjMatrix[i][j] ) degree[i]++;
		}
	}

	// now compute a set of vertices 

	done = false;

	while ( !done )
	{
		// find the unmarked vertex with the lowest degree

		int minD = sizeN + 1;
		int mini = 0;

		for ( i = 0; i < sizeN; i++ )
		{
			if ( degree[i] < minD && !marked[i] )
			{
				mini = i;
				minD = degree[mini];
			}
		}

		if ( minD < sizeN )
		{		
			// if a vertex was found, mark it and add it to the set

			set[mini]		= true;
			marked[mini]	= true;

			// mark every adjacent vertex because they can't be added to the set S

			for ( j = 0; j < sizeN; j++ ) 
			{
				if ( adjMatrix[mini][j] ) marked[j] = true;
			}
		}
		else
		{
			// no unmarked vertex was found

			done = true;
		}
	}

	// count the number of true bits in set to determine the cardinality of the set S

	setCnt	= total( set, sizeN );

	delete degree;
	delete marked;
}

/*
 *  function: edgePresent
 *  Description:  given two vertices within graph, return true if an edge exists between them,
 *                   otherwise, return false
 *  Precondition: graph is initialized, 0 < v1 < sizeN, 0 < v2 < sizeN
 *  Postcondition: return true if edge present, else return false 
*/
bool graph::edgePresent(int v1, int v2)
{
   if(( v1 < 0 ) || (v2 < 0) ) // illegal vertex values
      return(false);
   if(( v1 >= sizeN ) || (v2 >= sizeN) ) // illegal vertex values
      return(false);   
   return(adjMatrix[v1][v2]);
}

ostream& operator<< ( ostream& os, const graph& g )
{
	// print the adjacency matrix

	const int maxLineLength	= 50;

	int i, j, lineLength = 0;

	for ( i = 0; i < g.sizeN; i++ )
	{
		for ( j = 0; j < g.sizeN; j++ )
		{
			os << g.adjMatrix[i][j];
			if ( ++lineLength % maxLineLength == 0 ) os << endl;
		}
		os << endl;
	}
	os << endl;

	return os;
}
