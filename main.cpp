#include"bitmap.h"
#include"octree.h"
#include <ctime>
#include <mpi.h>

#define HEADER 255
#define RAY_ARRAY 254
#define NODE_ARRAY 253
#define MASTER 0

void traceRays(const vector<RayPixel>&, Octree&, BITMAP32&);
void masterFunc ();
void slaveFunc ();
void loadDicomsToOctree(Octree&);
void octreeToFile(Octree&);

int main (int argc, char **argv) {
	int nodeRank, resultSize;

	MPI_Init (&argc, &argv);

	MPI_Comm_rank (MPI_COMM_WORLD, &nodeRank); //Determine this node's rank

	if (nodeRank == MASTER) //If rank == 0
	        masterFunc ();
	else
	        slaveFunc ();

	MPI_Finalize ();
	return 0;
}

void masterFunc() {
	Octree t;
	
	vector3 cam(-0.5,-0.5, -800);
	vector3 camX(1,0,0);
	vector3 camY(0,1,0);
	vector3 camZ(0,0,1);
	
	BITMAP32 render(512,512);
	vector3 p;
	vector3 lightdir(-1,-1,1);
	vector3 lightdir2(0,1,0);
	lightdir.normalize();
	
	int treeSize;
	char * treeBuffer;
	
	//t.loadFromBitmaps("./dicom/IM_0001%03d.bmp", 200);
	//t.writeToFile("octree.dat");
	cout << "Loading octree data" << endl;
	t.readFromFile("octree.dat", treeBuffer, treeSize);
	
	MPI_Bcast (&treeSize, 1, MPI_INT, MASTER, MPI_COMM_WORLD);					//Broadcast header with tree size
	MPI_Bcast (treeBuffer,  treeSize, MPI_BYTE, MASTER, MPI_COMM_WORLD);      	//Broadcast tree
	cout << "Data sent from master node." << endl;
	int frames = 1;
	time_t startTime = time(0);
	for(int i = 0; i < frames; i++) {
		double xcenter = 30, zcenter = -25, radius = 500;
		RaytraceCamera camera(vector3(xcenter+radius*cos(i*2*PI/frames)+1,-9,zcenter+radius*sin(i*2*PI/frames)+1), vector3(xcenter,-10,zcenter));
		cout << "Generating rays..." << endl;
		vector<RayPixel> rays = camera.generateRays(512,512);
		cout << rays.size() << " rays generated." << endl;
		traceRays(rays, t, render);
		cout << "Loop " << i << ": done tracing rays!" << endl;
		time_t seconds = time(0);
		cout << "Frame " << i << " done at " << difftime(seconds, startTime) << endl;
		exportBMP(render,"render" + toString(i) + ".bmp");
	}

}

TracePixel traceRay(RayPixel ray, Octree& tree){
	vector<int> leaves;
	leaves.reserve(512);
	tree.trace(leaves,ray.r,false);
	float sum = 0;
	for (int k = 0; k < leaves.size(); k++) {
		sum += tree.nodes[leaves[k]].val;
	}
	return TracePixel(ray.x, ray.y, sum);
}

void traceRays(const vector<RayPixel>& rays, Octree& tree, BITMAP32& image) {
	int nodeCount;
	MPI_Comm_size (MPI_COMM_WORLD, &nodeCount);
	
	int totalIndex = 0;
	
	for (int i = 1; i < nodeCount; i++) {
		int chunkSize = rays.size() / (nodeCount - 1);
		if(i == nodeCount - 1) chunkSize += rays.size() % (nodeCount - 1);

		RayPixel * chunk = new RayPixel[chunkSize];

		for(int j = 0; j < chunkSize; j++, totalIndex++) {
			chunk[j] = rays[totalIndex];
		}
		
		MPI_Request request;
		MPI_Isend (&chunkSize, 1, MPI_INT, i, HEADER, MPI_COMM_WORLD, &request);
		MPI_Isend (chunk, chunkSize * sizeof(RayPixel), MPI_BYTE, i, RAY_ARRAY, MPI_COMM_WORLD, &request);
	}

	cout << "Done sending ray packets" << endl;
	
	for(int i = 1; i < nodeCount; i++){
		MPI_Status status;
		int chunkSize;
        MPI_Recv (&chunkSize, 1, MPI_INT, i, HEADER, MPI_COMM_WORLD, &status);
		
		TracePixel * traceChunk = new TracePixel[chunkSize];
		MPI_Recv (traceChunk, chunkSize * sizeof(TracePixel), MPI_BYTE, i, NODE_ARRAY, MPI_COMM_WORLD, &status);
		
		for(int j = 0; j < chunkSize; j++){
			int x = traceChunk[j].x, y = traceChunk[j].y;
			image(y,x).R = image(y,x).G = image(y,x).B = (unsigned char) (pow((traceChunk[j].val*2.5/255.0),0.5)*255.0);
		}
    }
}

void slaveFunc () {
	int nodeRank;
	MPI_Comm_rank(MPI_COMM_WORLD, &nodeRank);  
	
	int treeSize;
	MPI_Bcast (&treeSize, 1, MPI_INT, MASTER, MPI_COMM_WORLD);

	char * treeBuffer = new char[treeSize];
	MPI_Bcast (treeBuffer,  treeSize, MPI_BYTE, MASTER, MPI_COMM_WORLD);
	
	Octree t;
	t.readSerializedData(treeBuffer, treeSize);
	
	MPI_Status status;
	int chunkSize;
	MPI_Recv (&chunkSize, 1, MPI_INT, MASTER, HEADER, MPI_COMM_WORLD, &status);

	cout << "chunk size" << chunkSize << endl;
	RayPixel * chunk = new RayPixel[chunkSize];
	MPI_Recv (chunk, chunkSize * sizeof(RayPixel), MPI_BYTE, MASTER, RAY_ARRAY, MPI_COMM_WORLD, &status);

	TracePixel * traceChunk = new TracePixel[chunkSize];

	for (int i = 0; i < chunkSize; i++) {
		traceChunk[i] = traceRay(chunk[i], t);
	}
	cout << "done tracing client pack" << endl;
	
	MPI_Request request;
	MPI_Isend (&chunkSize, 1, MPI_INT, MASTER, HEADER, MPI_COMM_WORLD, &request);
	MPI_Isend (traceChunk, chunkSize * sizeof(TracePixel), MPI_BYTE, MASTER, NODE_ARRAY, MPI_COMM_WORLD, &request);
}

